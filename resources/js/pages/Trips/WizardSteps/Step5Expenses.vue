<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useToast } from '@/utils/toast';
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatCurrency } from '@/utils/functions';

const props = defineProps<{
  trip: any;
}>();

const emit = defineEmits(['complete']);
const toast = useToast();

interface Expense {
  description: string;
  amount: number;
  type: string;
}

const loading = ref(true);
const saving = ref(false);
const expenses = ref<Expense[]>([]);
const isEditable = computed(() => ['DRAFT', 'ONGOING'].includes(props.trip.status));
const existingExpenses = computed(() => props.trip.expenses || []);

const addExpense = () => {
  expenses.value.push({
    description: '',
    amount: 0,
    type: 'Trip Expense',
  });
};

const removeExpense = (index: number) => {
  expenses.value.splice(index, 1);
};

const submit = async () => {
  const validExpenses = expenses.value.filter(e => e.description && e.amount > 0);

  if (validExpenses.length === 0) {
    emit('complete');
    return;
  }

  saving.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}/add-expenses`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
      body: JSON.stringify({ expenses: validExpenses }),
    });

    if (!response.ok) throw new Error('Failed to save expenses');

    const data = await response.json();
    toast.success('Saved', 'Expenses added successfully');
    expenses.value = [];
    addExpense();
    emit('complete', data.data);
  } catch (error) {
    console.error('Save error:', error);
    toast.error('Error', 'Failed to save expenses');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loading.value = false;
  addExpense();
});
</script>

<template>
  <div class="glass-card p-6 rounded-xl border border-border/50 shadow-lg space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-foreground">Trip Expenses</h2>
        <p class="text-sm text-muted-foreground">Record expenses specific to this trip (Fuel, Food, etc.).</p>
      </div>
    </div>

    <!-- Existing Expenses -->
    <div v-if="existingExpenses.length > 0" class="space-y-3 mb-6">
      <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Existing Entries</h3>
      <div class="bg-muted/20 rounded-lg overflow-hidden border border-border/50">
        <table class="min-w-full divide-y divide-border">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Description</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Type</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Status</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="e in existingExpenses" :key="e.id">
              <td class="px-4 py-2 text-sm">{{ e.description }}</td>
              <td class="px-4 py-2 text-sm text-muted-foreground">{{ e.type }}</td>
              <td class="px-4 py-2 text-sm">
                <span :class="{
                  'px-2 py-0.5 rounded text-xs font-medium border': true,
                  'bg-yellow-100 text-yellow-800 border-yellow-200': e.status === 'PENDING',
                  'bg-green-100 text-green-800 border-green-200': e.status === 'APPROVED',
                  'bg-red-100 text-red-800 border-red-200': e.status === 'REJECTED'
                }">
                  {{ e.status }}
                </span>
              </td>
              <td class="px-4 py-2 text-sm text-right font-medium">{{ formatCurrency(e.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- New Expenses Form -->
    <div v-if="isEditable" class="space-y-4">
      <h3 class="text-sm font-medium text-foreground">Add New Expenses</h3>

      <div class="space-y-3">
        <div v-for="(expense, index) in expenses" :key="index" class="flex flex-col md:flex-row gap-3 items-end bg-orange-50/50 p-3 rounded-lg border border-orange-100">
          <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-muted-foreground mb-1">Description</label>
            <input
              v-model="expense.description"
              type="text"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm"
              placeholder="e.g. Fuel 50L"
            />
          </div>

          <div class="w-full md:w-40">
            <label class="block text-xs font-medium text-muted-foreground mb-1">Category</label>
            <input
              v-model="expense.type"
              type="text"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm"
              placeholder="General"
            />
          </div>

          <div class="w-full md:w-32">
            <label class="block text-xs font-medium text-muted-foreground mb-1">Amount</label>
            <input
              v-model="expense.amount"
              type="number"
              step="0.01"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm"
              placeholder="0.00"
            />
          </div>

          <button
            @click="removeExpense(index)"
            class="p-2 text-destructive hover:bg-destructive/10 rounded-lg transition-colors mb-0.5"
          >
            <TrashIcon class="h-5 w-5" />
          </button>
        </div>
      </div>

      <button
        @click="addExpense"
        class="text-sm text-primary hover:underline flex items-center"
      >
        <PlusIcon class="h-4 w-4 mr-1" />
        Add Another Line
      </button>
    </div>

    <div class="flex justify-end pt-4">
      <button
        v-if="isEditable"
        type="submit"
        @click="submit"
        :disabled="saving"
        class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 flex items-center gap-2"
      >
        <span v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
        {{ saving ? 'Save & Continue' : 'Save & Continue' }}
      </button>
      <button
        v-else
        type="button"
        @click="$emit('complete')"
        class="px-6 py-2 bg-secondary text-secondary-foreground rounded-lg font-medium hover:bg-secondary/90 transition-colors"
      >
        Next Step
      </button>
    </div>
  </div>
</template>
