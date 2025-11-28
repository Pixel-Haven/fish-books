<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useToast } from '@/utils/toast';
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatCurrency } from '@/utils/functions';
import Modal from '@/components/Modal.vue';

const props = defineProps<{
  trip: any;
}>();

const emit = defineEmits(['complete']);
const toast = useToast();

interface FishType {
  id: string;
  name: string;
}

interface BillLineItem {
  fish_type_id: string;
  quantity: number;
  price_per_kilo: number;
}

interface Bill {
  id?: string;
  bill_type: 'TODAY_SALES' | 'PREVIOUS_DAY_SALES' | 'OTHER';
  bill_no: string;
  vendor: string;
  bill_date: string;
  amount: number;
  payment_status: 'PAID' | 'UNPAID' | 'PARTIAL';
  line_items: BillLineItem[];
}

const loading = ref(true);
const saving = ref(false);
const fishTypes = ref<FishType[]>([]);
const newBills = ref<Bill[]>([]);
const isEditable = computed(() => ['DRAFT', 'ONGOING'].includes(props.trip.status));
const existingBills = computed(() => props.trip.bills || []);

// Modal for adding/editing bills
const showBillModal = ref(false);
const currentBill = ref<Bill>({
  bill_type: 'TODAY_SALES',
  bill_no: '',
  vendor: '',
  bill_date: props.trip.date, // Default to trip date
  amount: 0,
  payment_status: 'PAID',
  line_items: [],
});

const fetchFishTypes = async () => {
  try {
    const response = await fetch('/api/fish-types', {
      headers: { 'Accept': 'application/json' },
      credentials: 'include',
    });
    const result = await response.json();
    fishTypes.value = result.data || [];
  } catch (error) {
    console.error('Failed to fetch fish types:', error);
  } finally {
    loading.value = false;
  }
};

const openAddBillModal = () => {
  currentBill.value = {
    bill_type: 'TODAY_SALES',
    bill_no: '',
    vendor: '',
    bill_date: props.trip.date,
    amount: 0,
    payment_status: 'PAID',
    line_items: [],
  };
  addLineItem(); // Default 1 line item
  showBillModal.value = true;
};

const addLineItem = () => {
  currentBill.value.line_items.push({
    fish_type_id: '',
    quantity: 0,
    price_per_kilo: 0,
  });
};

const removeLineItem = (index: number) => {
  currentBill.value.line_items.splice(index, 1);
  updateBillAmount();
};

const updateBillAmount = () => {
  // Auto-calculate amount if line items exist
  if (currentBill.value.line_items.length > 0) {
    const total = currentBill.value.line_items.reduce((sum, item) => {
      return sum + (item.quantity * item.price_per_kilo);
    }, 0);
    currentBill.value.amount = total;
  }
};

const saveBillToLocal = () => {
  // Validate
  if (!currentBill.value.bill_no) {
    toast.error('Validation', 'Bill Number is required');
    return;
  }

  if (currentBill.value.line_items.length > 0) {
      const invalidItems = currentBill.value.line_items.some(item => !item.fish_type_id || item.quantity <= 0 || item.price_per_kilo <= 0);
      if (invalidItems) {
          toast.error('Validation', 'Please fill all line item details');
          return;
      }
  }

  // Add to newBills list
  newBills.value.push(JSON.parse(JSON.stringify(currentBill.value)));
  showBillModal.value = false;
};

const removeNewBill = (index: number) => {
  newBills.value.splice(index, 1);
};

const submit = async () => {
  if (newBills.value.length === 0) {
    // If no new bills, just proceed
    emit('complete');
    return;
  }

  saving.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}/add-bills`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
      body: JSON.stringify({ bills: newBills.value }),
    });

    if (!response.ok) throw new Error('Failed to save bills');

    const data = await response.json();
    toast.success('Saved', 'Bills updated successfully');
    newBills.value = [];
    emit('complete', data.data);
  } catch (error) {
    console.error('Save error:', error);
    toast.error('Error', 'Failed to save bills');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchFishTypes();
});
</script>

<template>
  <div class="glass-card p-6 rounded-xl border border-border/50 shadow-lg space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-foreground">Sales & Bills</h2>
        <p class="text-sm text-muted-foreground">Record sales from fish buyers.</p>
      </div>
      <button
        v-if="isEditable"
        @click="openAddBillModal"
        class="inline-flex items-center px-3 py-1.5 bg-secondary text-secondary-foreground text-sm font-medium rounded-lg hover:bg-secondary/90 transition-colors"
      >
        <PlusIcon class="h-4 w-4 mr-1" />
        Add Bill
      </button>
    </div>

    <!-- Existing Bills List -->
    <div v-if="existingBills.length > 0" class="space-y-4 mb-6">
      <h3 class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Existing Bills</h3>
      <div v-for="(bill, index) in existingBills" :key="'existing-' + index" class="bg-card p-4 rounded-lg border border-border/50 relative opacity-80">
        <div class="flex justify-between items-start mb-2">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ bill.bill_type?.replace(/_/g, ' ') }}</span>
            <h3 class="font-bold text-lg text-foreground">Bill #{{ bill.bill_no }}</h3>
            <p class="text-sm text-muted-foreground">{{ bill.vendor || 'Unknown Vendor' }} • {{ bill.bill_date }}</p>
          </div>
          <div class="text-right">
            <div class="text-xl font-bold text-primary">{{ formatCurrency(bill.amount) }}</div>
            <span class="text-xs px-2 py-0.5 rounded bg-success/10 text-success border border-success/20">{{ bill.payment_status }}</span>
          </div>
        </div>

        <!-- Line items preview -->
        <div v-if="bill.line_items && bill.line_items.length > 0" class="mt-3 pt-3 border-t border-border/30 text-sm">
          <p class="text-xs text-muted-foreground mb-1">Items:</p>
          <ul class="space-y-1">
            <li v-for="(item, idx) in bill.line_items" :key="idx" class="flex justify-between">
              <span>{{ item.fish_type?.name || 'Unknown Fish' }}</span>
              <span>{{ item.quantity }}kg × {{ formatCurrency(item.price_per_kilo) }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- New Bills List -->
    <div v-if="newBills.length > 0" class="space-y-4">
      <h3 class="text-sm font-medium text-foreground uppercase tracking-wider">New Bills (Pending Save)</h3>
      <div v-for="(bill, index) in newBills" :key="'new-' + index" class="bg-card p-4 rounded-lg border border-primary/30 relative shadow-sm ring-1 ring-primary/20">
        <div class="flex justify-between items-start mb-2">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ bill.bill_type.replace(/_/g, ' ') }}</span>
            <h3 class="font-bold text-lg text-foreground">Bill #{{ bill.bill_no }}</h3>
            <p class="text-sm text-muted-foreground">{{ bill.vendor || 'Unknown Vendor' }} • {{ bill.bill_date }}</p>
          </div>
          <div class="text-right">
            <div class="text-xl font-bold text-primary">{{ formatCurrency(bill.amount) }}</div>
            <span class="text-xs px-2 py-0.5 rounded bg-success/10 text-success border border-success/20">{{ bill.payment_status }}</span>
          </div>
        </div>

        <div v-if="bill.line_items.length > 0" class="mt-3 pt-3 border-t border-border/30 text-sm">
          <p class="text-xs text-muted-foreground mb-1">Items:</p>
          <ul class="space-y-1">
            <li v-for="(item, idx) in bill.line_items" :key="idx" class="flex justify-between">
              <span>{{ fishTypes.find(f => f.id == item.fish_type_id)?.name || 'Unknown Fish' }}</span>
              <span>{{ item.quantity }}kg × {{ formatCurrency(item.price_per_kilo) }}</span>
            </li>
          </ul>
        </div>

        <button
          v-if="isEditable"
          @click="removeNewBill(index)"
          class="absolute top-4 right-4 text-destructive hover:bg-destructive/10 p-1 rounded transition-colors"
          title="Remove Bill"
        >
          <TrashIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <div v-if="existingBills.length === 0 && newBills.length === 0" class="text-center py-8 bg-muted/20 rounded-lg border border-border/50 border-dashed">
      <p class="text-muted-foreground">No bills added yet.</p>
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
        {{ saving ? 'Save & Continue' : (newBills.length > 0 ? 'Save & Continue' : 'Continue') }}
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

    <!-- Add Bill Modal -->
    <Modal :show="showBillModal" @close="showBillModal = false">
      <div class="p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-foreground mb-4">Add Bill</h2>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Bill Type</label>
              <select v-model="currentBill.bill_type" class="w-full px-3 py-2 bg-background border border-input rounded-lg">
                <option value="TODAY_SALES">Today Sales</option>
                <option value="PREVIOUS_DAY_SALES">Previous Day Sales</option>
                <option value="OTHER">Other</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Bill No</label>
              <input v-model="currentBill.bill_no" type="text" class="w-full px-3 py-2 bg-background border border-input rounded-lg" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Vendor</label>
              <input v-model="currentBill.vendor" type="text" class="w-full px-3 py-2 bg-background border border-input rounded-lg" />
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Date</label>
              <input v-model="currentBill.bill_date" type="date" class="w-full px-3 py-2 bg-background border border-input rounded-lg" />
            </div>
          </div>

          <!-- Line Items Section -->
          <div class="border-t border-border pt-4 mt-4">
            <div class="flex justify-between items-center mb-2">
              <h3 class="font-medium">Line Items</h3>
              <button @click="addLineItem" class="text-xs text-primary hover:underline">+ Add Item</button>
            </div>

            <div class="space-y-2">
              <div v-for="(item, idx) in currentBill.line_items" :key="idx" class="flex gap-2 items-center bg-muted/20 p-2 rounded">
                <div class="flex-1">
                  <select v-model="item.fish_type_id" class="w-full px-2 py-1 bg-background border border-input rounded text-sm">
                    <option value="">Select Fish</option>
                    <option v-for="fish in fishTypes" :key="fish.id" :value="fish.id">{{ fish.name }}</option>
                  </select>
                </div>
                <div class="w-20">
                  <input
                    v-model="item.quantity"
                    type="number"
                    placeholder="Qty"
                    class="w-full px-2 py-1 bg-background border border-input rounded text-sm"
                    @input="updateBillAmount"
                  />
                </div>
                <div class="w-24">
                  <input
                    v-model="item.price_per_kilo"
                    type="number"
                    placeholder="Price"
                    class="w-full px-2 py-1 bg-background border border-input rounded text-sm"
                    @input="updateBillAmount"
                  />
                </div>
                <button @click="removeLineItem(idx)" class="text-destructive hover:text-destructive/80">
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 border-t border-border pt-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Payment Status</label>
              <select v-model="currentBill.payment_status" class="w-full px-3 py-2 bg-background border border-input rounded-lg">
                <option value="PAID">Paid</option>
                <option value="UNPAID">Unpaid</option>
                <option value="PARTIAL">Partial</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1">Total Amount</label>
              <input v-model="currentBill.amount" type="number" step="0.01" class="w-full px-3 py-2 bg-background border border-input rounded-lg font-bold" />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showBillModal = false" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
          <button @click="saveBillToLocal" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg">Add Bill</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
