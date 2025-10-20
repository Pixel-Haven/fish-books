<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';
import { formatDate, formatCurrency } from '@/utils/functions';
import Datepicker from '@/components/ui/datepicker.vue';
import { PlusIcon, MagnifyingGlassIcon, EyeIcon, TrashIcon, PencilIcon } from '@heroicons/vue/24/outline';
import { CalendarDays, FileText } from 'lucide-vue-next';

// Types
interface Vessel {
  id: string;
  name: string;
  registration_number: string;
}

interface WeeklySheet {
  id: string;
  vessel_id: string;
  vessel: Vessel;
  week_start: string;
  week_end: string;
  label: string | null;
  description: string | null;
  status: 'DRAFT' | 'READY_FOR_APPROVAL' | 'FINALIZED' | 'PAID';
  total_sales: string | null;
  total_expenses: string | null;
  crew_share: string | null;
  owner_share: string | null;
  trips_count?: number;
  closed_trips_count?: number;
  fishing_days_count?: number;
  created_at: string;
}

interface User {
  id: string;
  name: string;
  email: string;
  role: 'OWNER' | 'MANAGER';
}

interface PageProps {
  auth: {
    user: User | null;
  };
  [key: string]: any;
}

// Access authenticated user
const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const toast = useToast();

// State
const weeklySheets = ref<WeeklySheet[]>([]);
const vessels = ref<Vessel[]>([]);
const loading = ref(true);
const deleting = ref(false);

// Filters
const filters = ref({
  status: 'all',
  vessel_id: 'all',
  date_from: '',
  date_to: '',
  search: '',
});

// Delete modal
const showDeleteModal = ref(false);
const selectedSheet = ref<WeeklySheet | null>(null);

// Helper for CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

// Computed filtered sheets
const filteredSheets = computed(() => {
  let result = weeklySheets.value;

  // Filter by status
  if (filters.value.status !== 'all') {
    result = result.filter(sheet => sheet.status === filters.value.status);
  }

  // Filter by vessel
  if (filters.value.vessel_id !== 'all') {
    result = result.filter(sheet => sheet.vessel_id === filters.value.vessel_id);
  }

  // Filter by date range
  if (filters.value.date_from) {
    result = result.filter(sheet => sheet.week_start >= filters.value.date_from);
  }
  if (filters.value.date_to) {
    result = result.filter(sheet => sheet.week_end <= filters.value.date_to);
  }

  // Search by label or vessel name
  if (filters.value.search) {
    const searchLower = filters.value.search.toLowerCase();
    result = result.filter(sheet => 
      sheet.label?.toLowerCase().includes(searchLower) ||
      sheet.vessel.name.toLowerCase().includes(searchLower) ||
      sheet.vessel.registration_number.toLowerCase().includes(searchLower)
    );
  }

  return result;
});

// Fetch weekly sheets
const fetchWeeklySheets = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/weekly-sheets', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    
    // Handle both paginated and non-paginated responses
    if (result.data) {
      weeklySheets.value = Array.isArray(result.data) ? result.data : [];
    } else if (Array.isArray(result)) {
      weeklySheets.value = result;
    } else {
      weeklySheets.value = [];
    }
  } catch (error) {
    console.error('Failed to fetch weekly sheets:', error);
    weeklySheets.value = []; // Ensure it's always an array
    toast.error('Failed to load weekly sheets', 'Please try again.');
  } finally {
    loading.value = false;
  }
};

// Fetch vessels for filter
const fetchVessels = async () => {
  try {
    const response = await fetch('/api/vessels', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    
    // Handle both paginated and non-paginated responses
    if (result.data) {
      vessels.value = Array.isArray(result.data) ? result.data : [];
    } else if (Array.isArray(result)) {
      vessels.value = result;
    } else {
      vessels.value = [];
    }
  } catch (error) {
    console.error('Failed to fetch vessels:', error);
    vessels.value = []; // Ensure it's always an array
  }
};

// Note: formatDate and formatCurrency are imported from utils

// Get status badge color
const getStatusClass = (status: string): string => {
  switch (status) {
    case 'PAID':
      return 'bg-success/10 text-success border-success/20';
    case 'FINALIZED':
      return 'bg-seafoam/10 text-seafoam border-seafoam/20';
    case 'READY_FOR_APPROVAL':
      return 'bg-warning/10 text-warning border-warning/20';
    case 'DRAFT':
    default:
      return 'bg-muted text-muted-foreground border-border';
  }
};

// Get status label
const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'READY_FOR_APPROVAL':
      return 'Ready';
    case 'FINALIZED':
      return 'Finalized';
    case 'PAID':
      return 'Paid';
    case 'DRAFT':
    default:
      return 'Draft';
  }
};

// Navigate to create page
const createWeeklySheet = () => {
  router.visit('/weekly-sheets/create');
};

// Navigate to detail page
const viewSheet = (sheet: WeeklySheet) => {
  router.visit(`/weekly-sheets/${sheet.id}`);
};

// Navigate to edit page
const editSheet = (sheet: WeeklySheet) => {
  router.visit(`/weekly-sheets/${sheet.id}/edit`);
};

// Confirm delete
const confirmDelete = (sheet: WeeklySheet) => {
  selectedSheet.value = sheet;
  showDeleteModal.value = true;
};

// Handle delete
const handleDelete = async () => {
  if (!selectedSheet.value) return;

  deleting.value = true;
  try {
    const response = await fetch(`/api/weekly-sheets/${selectedSheet.value.id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (!response.ok) {
      const data = await response.json();
      toast.error('Delete failed', data.message || 'Please try again.');
      return;
    }

    toast.success('Weekly sheet deleted', 'The weekly sheet and its trips have been removed.');
    showDeleteModal.value = false;
    fetchWeeklySheets();
  } catch (error) {
    console.error('Failed to delete weekly sheet:', error);
    toast.error('Delete failed', 'Please try again.');
  } finally {
    deleting.value = false;
  }
};

// Reset filters
const resetFilters = () => {
  filters.value = {
    status: 'all',
    vessel_id: 'all',
    date_from: '',
    date_to: '',
    search: '',
  };
};

onMounted(() => {
  fetchWeeklySheets();
  fetchVessels();
});
</script>

<template>
  <Head title="Weekly Sheets" />
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <CalendarDays :size="28" class="text-primary" />
          <div>
            <h1 class="text-3xl font-bold text-foreground">Weekly Sheets</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Manage weekly fishing schedules, trips, and crew payouts
            </p>
          </div>
        </div>
        <button
          @click="createWeeklySheet"
          class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2"
        >
          <PlusIcon class="h-5 w-5" />
          Create New Week
        </button>
      </div>

      <!-- Filters -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm space-y-4 overflow-visible">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <!-- Search -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-foreground mb-2">Search</label>
            <div class="relative">
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by label or vessel..."
                class="w-full pl-10 pr-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
              />
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
            </div>
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Status</label>
            <select
              v-model="filters.status"
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
            >
              <option value="all">All Statuses</option>
              <option value="DRAFT">Draft</option>
              <option value="READY_FOR_APPROVAL">Ready for Approval</option>
              <option value="FINALIZED">Finalized</option>
              <option value="PAID">Paid</option>
            </select>
          </div>

          <!-- Vessel Filter -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Vessel</label>
            <select
              v-model="filters.vessel_id"
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
            >
              <option value="all">All Vessels</option>
              <option v-for="vessel in vessels" :key="vessel.id" :value="vessel.id">
                {{ vessel.name }}
              </option>
            </select>
          </div>

          <!-- Reset Button -->
          <div class="flex items-end">
            <button
              @click="resetFilters"
              class="w-full px-4 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground font-medium rounded-lg transition-all duration-200"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Week Start From</label>
            <Datepicker
              v-model="filters.date_from"
              placeholder="Select start date"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">Week Start To</label>
            <Datepicker
              v-model="filters.date_to"
              placeholder="Select end date"
            />
          </div>
        </div>
      </div>

      <!-- Weekly Sheets List -->
      <div class="glass-card rounded-xl border border-border/50 shadow-lg overflow-hidden">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="filteredSheets.length === 0" class="text-center py-12">
          <CalendarDays :size="48" class="mx-auto text-muted-foreground" />
          <h3 class="mt-2 text-sm font-medium text-foreground">No weekly sheets</h3>
          <p class="mt-1 text-sm text-muted-foreground">Get started by creating a new weekly sheet.</p>
          <div class="mt-6">
            <button
              @click="createWeeklySheet"
              class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
            >
              <PlusIcon class="mr-2 h-5 w-5" />
              Create New Week
            </button>
          </div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Week Period
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Vessel
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Trips
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Total Sales
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Crew Share
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-card divide-y divide-border">
              <tr
                v-for="sheet in filteredSheets"
                :key="sheet.id"
                class="hover:bg-accent/50 transition-colors cursor-pointer"
                @click="viewSheet(sheet)"
              >
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-foreground">
                    {{ sheet.label || `Week ${formatDate(sheet.week_start)}` }}
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ formatDate(sheet.week_start) }} - {{ formatDate(sheet.week_end) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-foreground">{{ sheet.vessel.name }}</div>
                  <div class="text-sm text-muted-foreground">{{ sheet.vessel.registration_number }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-foreground">
                    <span class="font-medium">{{ sheet.trips_count || 0 }}</span> trips
                  </div>
                  <div class="text-sm text-muted-foreground" v-if="sheet.fishing_days_count !== undefined">
                    {{ sheet.fishing_days_count }} fishing days
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-foreground font-medium">
                  {{ formatCurrency(sheet.total_sales) }}
                </td>
                <td class="px-6 py-4 text-sm text-foreground font-medium">
                  {{ formatCurrency(sheet.crew_share) }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="getStatusClass(sheet.status)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                  >
                    {{ getStatusLabel(sheet.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-medium space-x-2" @click.stop>
                  <button
                    @click="viewSheet(sheet)"
                    class="text-primary hover:text-primary/80 transition-colors"
                    title="View Details"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </button>
                  <button
                    v-if="sheet.status === 'DRAFT'"
                    @click="editSheet(sheet)"
                    class="text-secondary hover:text-secondary/80 transition-colors"
                    title="Edit"
                  >
                    <PencilIcon class="h-5 w-5" />
                  </button>
                  <button
                    v-if="sheet.status === 'DRAFT' && user?.role === 'OWNER'"
                    @click="confirmDelete(sheet)"
                    class="text-destructive hover:text-destructive/80 transition-colors"
                    title="Delete"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4" v-if="filteredSheets.length > 0">
        <div class="glass-card rounded-lg p-4 border border-border/50">
          <div class="text-sm text-muted-foreground">Total Weeks</div>
          <div class="text-2xl font-bold text-foreground mt-1">{{ filteredSheets.length }}</div>
        </div>
        <div class="glass-card rounded-lg p-4 border border-border/50">
          <div class="text-sm text-muted-foreground">Draft</div>
          <div class="text-2xl font-bold text-foreground mt-1">
            {{ filteredSheets.filter(s => s.status === 'DRAFT').length }}
          </div>
        </div>
        <div class="glass-card rounded-lg p-4 border border-border/50">
          <div class="text-sm text-muted-foreground">Finalized</div>
          <div class="text-2xl font-bold text-foreground mt-1">
            {{ filteredSheets.filter(s => s.status === 'FINALIZED').length }}
          </div>
        </div>
        <div class="glass-card rounded-lg p-4 border border-border/50">
          <div class="text-sm text-muted-foreground">Paid</div>
          <div class="text-2xl font-bold text-success mt-1">
            {{ filteredSheets.filter(s => s.status === 'PAID').length }}
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Delete Weekly Sheet"
      :message="`Are you sure you want to delete the weekly sheet for <strong>${selectedSheet?.vessel.name}</strong> (${selectedSheet ? formatDate(selectedSheet.week_start) : ''})? This will also delete all associated trips and data.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </MainLayout>
</template>
