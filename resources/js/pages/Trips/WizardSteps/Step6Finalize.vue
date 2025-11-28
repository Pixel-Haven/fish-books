<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/utils/toast';
import { formatCurrency } from '@/utils/functions';
import { CheckBadgeIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  trip: any;
  calculations: any;
}>();

const emit = defineEmits(['complete', 'refresh']);
const toast = useToast();
const page = usePage();
const finalizing = ref(false);
const closing = ref(false);

const isOwner = computed(() => page.props.auth.user?.role === 'OWNER');
const canFinalize = computed(() => props.trip.status === 'DRAFT' && isOwner.value);
const canClose = computed(() => props.trip.status === 'ONGOING' && isOwner.value);
const canReopen = computed(() => props.trip.status === 'CLOSED' && isOwner.value);

const finalizeTrip = async () => {
  if (!confirm('Are you sure you want to finalize this trip? It will move to ONGOING status.')) return;

  finalizing.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}/finalize`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
    });

    if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Finalization failed');
    }

    toast.success('Finalized', 'Trip is now ONGOING');
    emit('refresh');
  } catch (error) {
    console.error('Error:', error);
    toast.error('Error', error instanceof Error ? error.message : 'Failed to finalize trip');
  } finally {
    finalizing.value = false;
  }
};

const closeTrip = async () => {
  if (!confirm('Are you sure you want to CLOSE this trip? This will lock all edits.')) return;

  closing.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}/close`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
    });

    if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'Closing failed');
    }

    toast.success('Closed', 'Trip is now CLOSED');
    emit('refresh');
  } catch (error) {
    console.error('Error:', error);
    toast.error('Error', error instanceof Error ? error.message : 'Failed to close trip');
  } finally {
    closing.value = false;
  }
};

const reopenTrip = async () => {
  if (!confirm('Reopen trip? This will move it back to ONGOING status.')) return;

  try {
    const response = await fetch(`/api/trips/${props.trip.id}/reopen`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        credentials: 'include',
    });

    if (!response.ok) throw new Error('Failed to reopen');

    toast.success('Reopened', 'Trip is now ONGOING');
    emit('refresh');
  } catch(error) {
      toast.error('Error', 'Failed to reopen trip');
  }
}
</script>

<template>
  <div class="glass-card p-6 rounded-xl border border-border/50 shadow-lg space-y-8">
    <div class="flex items-center gap-3">
      <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
        <CheckBadgeIcon class="h-6 w-6" />
      </div>
      <div>
        <h2 class="text-xl font-semibold text-foreground">Review & Finalize</h2>
        <p class="text-sm text-muted-foreground">Review the calculations and lock the trip.</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="calculations">
      <div class="bg-card p-4 rounded-lg border border-border/50">
        <p class="text-sm text-muted-foreground">Total Revenue</p>
        <p class="text-2xl font-bold text-foreground">{{ formatCurrency(calculations.total_sales) }}</p>
        <div class="mt-2 text-xs space-y-1 text-muted-foreground">
          <div class="flex justify-between">
            <span>Bill Sales:</span>
            <span>{{ formatCurrency(calculations.bill_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Fish Purchases:</span>
            <span>{{ formatCurrency(calculations.purchase_total) }}</span>
          </div>
        </div>
      </div>

      <div class="bg-card p-4 rounded-lg border border-border/50">
        <p class="text-sm text-muted-foreground">Total Expenses</p>
        <p class="text-2xl font-bold text-destructive">{{ formatCurrency(calculations.total_expenses) }}</p>
        <div class="mt-2 text-xs space-y-1 text-muted-foreground">
          <div class="flex justify-between">
            <span>Approved:</span>
            <span>{{ formatCurrency(calculations.approved_expenses) }}</span>
          </div>
          <div class="flex justify-between">
            <span>Weekly Share:</span>
            <span>{{ formatCurrency(calculations.weekly_expense_share) }}</span>
          </div>
        </div>
      </div>

      <div class="bg-card p-4 rounded-lg border border-border/50">
        <p class="text-sm text-muted-foreground">Net Total</p>
        <p class="text-2xl font-bold text-primary">{{ formatCurrency(calculations.net_total) }}</p>
        <div class="mt-2 text-xs space-y-1 text-muted-foreground">
            <div class="flex justify-between">
                <span>Vessel Maintenance (10%):</span>
                <span>{{ formatCurrency(calculations.vessel_maintenance) }}</span>
            </div>
        </div>
      </div>
    </div>

    <!-- Shares -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="calculations">
        <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-lg">
            <h3 class="font-semibold text-blue-900 mb-2">Owner Share (1/3)</h3>
            <p class="text-3xl font-bold text-blue-700">{{ formatCurrency(calculations.owner_share) }}</p>
        </div>
        <div class="bg-green-50/50 border border-green-100 p-4 rounded-lg">
            <h3 class="font-semibold text-green-900 mb-2">Crew Share (2/3)</h3>
            <p class="text-3xl font-bold text-green-700">{{ formatCurrency(calculations.crew_share) }}</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="border-t border-border pt-6">
        <div v-if="trip.status === 'DRAFT'" class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-orange-600 bg-orange-50 px-3 py-2 rounded-lg border border-orange-100">
                <ExclamationTriangleIcon class="h-5 w-5" />
                <span class="text-sm font-medium">Trip is currently in DRAFT</span>
            </div>

            <button
                v-if="isOwner"
                @click="finalizeTrip"
                :disabled="finalizing"
                class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-bold hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
            >
                {{ finalizing ? 'Finalizing...' : 'Finalize Trip' }}
            </button>
            <div v-else class="text-sm text-muted-foreground italic">
                Only the Owner can finalize this trip.
            </div>
        </div>

        <div v-else-if="trip.status === 'ONGOING'" class="flex items-center justify-between">
             <div class="flex items-center gap-2 text-blue-600 bg-blue-50 px-3 py-2 rounded-lg border border-blue-100">
                <CheckBadgeIcon class="h-5 w-5" />
                <span class="text-sm font-medium">Trip is ONGOING. You can still add bills/expenses.</span>
            </div>

            <button
                v-if="isOwner"
                @click="closeTrip"
                :disabled="closing"
                class="px-6 py-2 bg-seafoam text-white rounded-lg font-bold hover:bg-seafoam/90 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
            >
                {{ closing ? 'Closing...' : 'Close Trip' }}
            </button>
             <div v-else class="text-sm text-muted-foreground italic">
                Only the Owner can close this trip.
            </div>
        </div>

        <div v-else-if="trip.status === 'CLOSED'" class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-green-600 bg-green-50 px-3 py-2 rounded-lg border border-green-100">
                <CheckBadgeIcon class="h-5 w-5" />
                <span class="text-sm font-medium">Trip is CLOSED. Locked for editing.</span>
            </div>

             <button
                v-if="isOwner"
                @click="reopenTrip"
                class="px-4 py-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded-lg transition-colors text-sm font-medium"
            >
                Reopen Trip
            </button>
        </div>
    </div>
  </div>
</template>
