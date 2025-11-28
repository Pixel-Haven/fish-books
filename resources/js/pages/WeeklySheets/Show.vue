<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import Modal from '@/components/Modal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';
import { formatDate, formatCurrency } from '@/utils/functions';
import {
  CalendarDaysIcon,
  ChevronLeftIcon,
  CurrencyDollarIcon,
  BanknotesIcon,
  ChartPieIcon,
  UserGroupIcon,
  CalculatorIcon,
  CheckBadgeIcon,
  PlusIcon,
  TrashIcon,
  PencilIcon,
} from '@heroicons/vue/24/outline';

// Types
interface Trip {
  id: string;
  day_of_week: string;
  date: string;
  is_fishing_day: boolean;
  status: 'DRAFT' | 'ONGOING' | 'CLOSED';
  total_sales: string | null;
  total_expenses: string | null;
  net_total: string | null;
}

interface WeeklyExpense {
  id: string;
  amount: string;
  description: string;
  category?: string;
}

interface CrewCredit {
  id: string;
  crew_member_id: string;
  amount: string;
  description: string;
  crew_member: {
    name: string;
    id_card_number: string;
  };
}

interface WeeklySheet {
  id: string;
  vessel: {
    name: string;
    registration_number: string;
  };
  week_start: string;
  week_end: string;
  label: string | null;
  description: string | null;
  status: 'DRAFT' | 'READY_FOR_APPROVAL' | 'FINALIZED' | 'PAID';
  total_sales: string | null;
  total_expenses: string | null;
  crew_share: string | null;
  owner_share: string | null;
  trips: Trip[];
  weekly_expenses: WeeklyExpense[];
  crew_credits: CrewCredit[];
}

interface CrewMember {
  id: string;
  name: string;
  id_card_number: string;
}

// Props
const props = defineProps<{
  id?: string;
}>();

const page = usePage();
const toast = useToast();
const sheetId = computed(() => {
  // Extract ID from URL if not passed as prop (handling Inertia route params)
  const url = page.url;
  const match = url.match(/\/weekly-sheets\/([^\/]+)/);
  return match ? match[1] : '';
});

// State
const sheet = ref<WeeklySheet | null>(null);
const loading = ref(true);
const calculating = ref(false);
const finalizing = ref(false);
const crewMembers = ref<CrewMember[]>([]);

// Expense Modal State
const showExpenseModal = ref(false);
const expenseForm = ref({
  amount: '',
  description: '',
  type: 'General',
});
const submittingExpense = ref(false);

// Credit Modal State
const showCreditModal = ref(false);
const creditForm = ref({
  crew_member_id: '',
  amount: '',
  description: '',
});
const submittingCredit = ref(false);

// Helper for CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

// Fetch Sheet Data
const fetchSheet = async () => {
  if (!sheetId.value) return;

  loading.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });

    if (!response.ok) throw new Error('Failed to load sheet');

    sheet.value = await response.json();
  } catch (error) {
    console.error('Error fetching sheet:', error);
    toast.error('Error', 'Failed to load weekly sheet details');
  } finally {
    loading.value = false;
  }
};

// Fetch Crew Members (for credits)
const fetchCrewMembers = async () => {
  try {
    const response = await fetch('/api/crew-members?active=true', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    const result = await response.json();
    crewMembers.value = result.data || [];
  } catch (error) {
    console.error('Failed to fetch crew:', error);
  }
};

// Add Weekly Expense
const addExpense = async () => {
  if (!expenseForm.value.amount || !expenseForm.value.description) {
    toast.error('Validation Error', 'Please fill in all fields');
    return;
  }

  submittingExpense.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}/add-expenses`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
      body: JSON.stringify({
        expenses: [{
          amount: parseFloat(expenseForm.value.amount),
          description: expenseForm.value.description,
          type: expenseForm.value.type,
        }]
      }),
    });

    if (!response.ok) throw new Error('Failed to add expense');

    toast.success('Success', 'Expense added successfully');
    showExpenseModal.value = false;
    expenseForm.value = { amount: '', description: '', type: 'General' };
    fetchSheet(); // Refresh data
  } catch (error) {
    console.error('Error adding expense:', error);
    toast.error('Error', 'Failed to add expense');
  } finally {
    submittingExpense.value = false;
  }
};

// Add Crew Credit
const addCredit = async () => {
  if (!creditForm.value.amount || !creditForm.value.crew_member_id || !creditForm.value.description) {
    toast.error('Validation Error', 'Please fill in all fields');
    return;
  }

  submittingCredit.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}/add-credits`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
      body: JSON.stringify({
        credits: [{
          crew_member_id: creditForm.value.crew_member_id,
          amount: parseFloat(creditForm.value.amount),
          description: creditForm.value.description,
        }]
      }),
    });

    if (!response.ok) throw new Error('Failed to add credit');

    toast.success('Success', 'Credit added successfully');
    showCreditModal.value = false;
    creditForm.value = { crew_member_id: '', amount: '', description: '' };
    fetchSheet();
  } catch (error) {
    console.error('Error adding credit:', error);
    toast.error('Error', 'Failed to add credit');
  } finally {
    submittingCredit.value = false;
  }
};

// Calculate Sheet
const calculateSheet = async () => {
  calculating.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}/calculate`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });

    if (!response.ok) throw new Error('Calculation failed');

    const result = await response.json();
    toast.success('Calculated', 'Weekly totals updated based on current data');
    // Ideally we'd update local state with result.calculations, but refreshing full sheet is safer
    fetchSheet();
  } catch (error) {
    console.error('Calculation error:', error);
    toast.error('Error', 'Failed to calculate weekly totals');
  } finally {
    calculating.value = false;
  }
};

// Finalize Sheet
const finalizeSheet = async () => {
  if (!confirm('Are you sure you want to finalize this week? This will lock all trips and expenses.')) return;

  finalizing.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}/finalize`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Finalization failed');
    }

    toast.success('Finalized', 'Weekly sheet has been finalized and payouts generated');
    fetchSheet();
  } catch (error) {
    console.error('Finalization error:', error);
    toast.error('Error', error instanceof Error ? error.message : 'Failed to finalize sheet');
  } finally {
    finalizing.value = false;
  }
};

// Mark as Paid
const markAsPaid = async () => {
  if (!confirm('Mark this week as PAID? This will update all payout records.')) return;

  try {
    const response = await fetch(`/api/weekly-sheets/${sheetId.value}/mark-as-paid`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });

    if (!response.ok) {
        throw new Error('Action failed');
    }

    toast.success('Paid', 'Weekly sheet marked as paid');
    fetchSheet();
  } catch (error) {
    console.error('Error:', error);
    toast.error('Error', 'Failed to mark as paid');
  }
};

// Navigation
const openTrip = (tripId: string) => {
  router.visit(`/trips/${tripId}`);
};

onMounted(() => {
  fetchSheet();
  fetchCrewMembers();
});
</script>

<template>
  <Head title="Weekly Sheet Details" />
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
    </div>

    <div v-else-if="sheet" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <button @click="router.visit('/weekly-sheets')" class="flex items-center text-sm text-muted-foreground hover:text-foreground mb-2 transition-colors">
            <ChevronLeftIcon class="h-4 w-4 mr-1" />
            Back to Weekly Sheets
          </button>
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-foreground">
              {{ sheet.label || `Week of ${formatDate(sheet.week_start)}` }}
            </h1>
            <span :class="{
              'px-3 py-1 rounded-full text-xs font-medium border': true,
              'bg-muted text-muted-foreground border-border': sheet.status === 'DRAFT',
              'bg-warning/10 text-warning border-warning/20': sheet.status === 'READY_FOR_APPROVAL',
              'bg-seafoam/10 text-seafoam border-seafoam/20': sheet.status === 'FINALIZED',
              'bg-success/10 text-success border-success/20': sheet.status === 'PAID',
            }">
              {{ sheet.status.replace(/_/g, ' ') }}
            </span>
          </div>
          <p class="text-muted-foreground mt-1">
            {{ sheet.vessel.name }} • {{ formatDate(sheet.week_start) }} - {{ formatDate(sheet.week_end) }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="sheet.status !== 'FINALIZED' && sheet.status !== 'PAID'"
            @click="calculateSheet"
            :disabled="calculating"
            class="inline-flex items-center px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/90 rounded-lg font-medium transition-colors"
          >
            <CalculatorIcon class="h-5 w-5 mr-2" />
            {{ calculating ? 'Calculating...' : 'Calculate' }}
          </button>

          <button
            v-if="sheet.status !== 'FINALIZED' && sheet.status !== 'PAID' && page.props.auth.user?.role === 'OWNER'"
            @click="finalizeSheet"
            :disabled="finalizing"
            class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-lg font-medium transition-colors"
          >
            <CheckBadgeIcon class="h-5 w-5 mr-2" />
            {{ finalizing ? 'Finalizing...' : 'Finalize Week' }}
          </button>

          <button
            v-if="sheet.status === 'FINALIZED' && page.props.auth.user?.role === 'OWNER'"
            @click="markAsPaid"
            class="inline-flex items-center px-4 py-2 bg-success text-white hover:bg-success/90 rounded-lg font-medium transition-colors"
          >
            <BanknotesIcon class="h-5 w-5 mr-2" />
            Mark as Paid
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-xl border border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Total Sales</p>
              <p class="text-2xl font-bold text-foreground">{{ formatCurrency(sheet.total_sales) }}</p>
            </div>
            <div class="h-10 w-10 rounded-full bg-blue-500/10 flex items-center justify-center">
              <CurrencyDollarIcon class="h-6 w-6 text-blue-500" />
            </div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-xl border border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Total Expenses</p>
              <p class="text-2xl font-bold text-destructive">{{ formatCurrency(sheet.total_expenses) }}</p>
            </div>
            <div class="h-10 w-10 rounded-full bg-red-500/10 flex items-center justify-center">
              <BanknotesIcon class="h-6 w-6 text-red-500" />
            </div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-xl border border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Crew Share</p>
              <p class="text-2xl font-bold text-seafoam">{{ formatCurrency(sheet.crew_share) }}</p>
            </div>
            <div class="h-10 w-10 rounded-full bg-seafoam/10 flex items-center justify-center">
              <UserGroupIcon class="h-6 w-6 text-seafoam" />
            </div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-xl border border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-muted-foreground">Owner Share</p>
              <p class="text-2xl font-bold text-primary">{{ formatCurrency(sheet.owner_share) }}</p>
            </div>
            <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
              <ChartPieIcon class="h-6 w-6 text-primary" />
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Trips List (Left Column - 2 spans) -->
        <div class="lg:col-span-2 space-y-4">
          <h2 class="text-xl font-semibold text-foreground flex items-center gap-2">
            <CalendarDaysIcon class="h-5 w-5" />
            Trips (Sat - Thu)
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="trip in sheet.trips"
              :key="trip.id"
              @click="openTrip(trip.id)"
              class="group relative glass-card p-4 rounded-xl border border-border/50 hover:border-primary/50 transition-all cursor-pointer hover:shadow-lg hover:-translate-y-1"
            >
              <div class="absolute top-4 right-4">
                <span :class="{
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium': true,
                  'bg-muted text-muted-foreground': trip.status === 'DRAFT',
                  'bg-blue-500/10 text-blue-500': trip.status === 'ONGOING',
                  'bg-seafoam/10 text-seafoam': trip.status === 'CLOSED'
                }">
                  {{ trip.status }}
                </span>
              </div>

              <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center font-bold text-primary">
                  {{ trip.day_of_week.substring(0, 3) }}
                </div>
                <div>
                  <p class="font-medium text-foreground">{{ formatDate(trip.date) }}</p>
                  <p class="text-xs text-muted-foreground">{{ trip.is_fishing_day ? 'Fishing Day' : 'Rest Day' }}</p>
                </div>
              </div>

              <div class="space-y-1 pt-2 border-t border-border/50">
                <div class="flex justify-between text-sm">
                  <span class="text-muted-foreground">Sales</span>
                  <span class="font-medium text-foreground">{{ formatCurrency(trip.total_sales || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-muted-foreground">Net Total</span>
                  <span class="font-medium text-foreground">{{ formatCurrency(trip.net_total || 0) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar (Right Column - 1 span) -->
        <div class="space-y-6">
          <!-- Weekly Expenses -->
          <div class="glass-card p-6 rounded-xl border border-border/50">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-foreground">Weekly Expenses</h3>
              <button
                v-if="sheet.status !== 'FINALIZED'"
                @click="showExpenseModal = true"
                class="p-1 hover:bg-muted rounded-full transition-colors"
              >
                <PlusIcon class="h-5 w-5 text-primary" />
              </button>
            </div>

            <div v-if="sheet.weekly_expenses.length === 0" class="text-center py-6 text-muted-foreground text-sm">
              No weekly expenses added
            </div>

            <ul v-else class="space-y-3">
              <li v-for="expense in sheet.weekly_expenses" :key="expense.id" class="flex justify-between items-start text-sm">
                <div>
                  <p class="font-medium text-foreground">{{ expense.description }}</p>
                  <p class="text-xs text-muted-foreground">{{ expense.category }}</p>
                </div>
                <span class="font-medium text-destructive">-{{ formatCurrency(expense.amount) }}</span>
              </li>
            </ul>
          </div>

          <!-- Crew Credits -->
          <div class="glass-card p-6 rounded-xl border border-border/50">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-foreground">Crew Credits</h3>
              <button
                v-if="sheet.status !== 'FINALIZED'"
                @click="showCreditModal = true"
                class="p-1 hover:bg-muted rounded-full transition-colors"
              >
                <PlusIcon class="h-5 w-5 text-primary" />
              </button>
            </div>

            <div v-if="sheet.crew_credits.length === 0" class="text-center py-6 text-muted-foreground text-sm">
              No crew credits added
            </div>

            <ul v-else class="space-y-3">
              <li v-for="credit in sheet.crew_credits" :key="credit.id" class="flex justify-between items-start text-sm">
                <div>
                  <p class="font-medium text-foreground">{{ credit.crew_member.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ credit.description }}</p>
                </div>
                <span class="font-medium text-orange-500">{{ formatCurrency(credit.amount) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Expense Modal -->
    <Modal :show="showExpenseModal" @close="showExpenseModal = false">
      <div class="p-6">
        <h2 class="text-xl font-bold text-foreground mb-4">Add Weekly Expense</h2>
        <form @submit.prevent="addExpense" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Description</label>
            <input
              v-model="expenseForm.description"
              type="text"
              required
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="e.g. Weekly Food Supplies"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Amount (MVR)</label>
            <input
              v-model="expenseForm.amount"
              type="number"
              step="0.01"
              required
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Category (Optional)</label>
            <input
              v-model="expenseForm.type"
              type="text"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="General"
            />
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              type="button"
              @click="showExpenseModal = false"
              class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingExpense"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors disabled:opacity-50"
            >
              {{ submittingExpense ? 'Adding...' : 'Add Expense' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Add Credit Modal -->
    <Modal :show="showCreditModal" @close="showCreditModal = false">
      <div class="p-6">
        <h2 class="text-xl font-bold text-foreground mb-4">Add Crew Credit</h2>
        <form @submit.prevent="addCredit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Crew Member</label>
            <select
              v-model="creditForm.crew_member_id"
              required
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
            >
              <option value="">Select Crew Member</option>
              <option v-for="crew in crewMembers" :key="crew.id" :value="crew.id">
                {{ crew.name }} ({{ crew.id_card_number }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Amount (MVR)</label>
            <input
              v-model="creditForm.amount"
              type="number"
              step="0.01"
              required
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="0.00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Description</label>
            <input
              v-model="creditForm.description"
              type="text"
              required
              class="w-full px-3 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="Reason for credit/advance"
            />
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              type="button"
              @click="showCreditModal = false"
              class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingCredit"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors disabled:opacity-50"
            >
              {{ submittingCredit ? 'Adding...' : 'Add Credit' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </MainLayout>
</template>
