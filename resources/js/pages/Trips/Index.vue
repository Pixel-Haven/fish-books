<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';
import { formatDate, formatCurrency } from '@/utils/functions';
import Datepicker from '@/components/ui/datepicker.vue';
import { EyeIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { Waves, Calendar, Ship, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';

// Types
interface Trip {
  id: string;
  vessel: {
    id: string;
    name: string;
  };
  weekly_sheet?: {
    id: string;
    week_start: string;
    week_end: string;
  };
  date: string;
  day_of_week: string | null;
  is_fishing_day: boolean;
  status: 'DRAFT' | 'ONGOING' | 'CLOSED';
  total_sales: string;
  crew_share: string;
  owner_share: string;
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

// State
const trips = ref<Trip[]>([]);
const loading = ref(true);

// Filters
const filters = ref({
  vessel_id: '',
  status: '',
  date_from: '',
  date_to: '',
  search: '',
});

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

// Delete modal
const showDeleteModal = ref(false);
const deleting = ref(false);
const selectedTrip = ref<Trip | null>(null);

const toast = useToast();

// Helper for CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

// Computed
const filteredTripsCount = computed(() => pagination.value.total);

const hasActiveFilters = computed(() => {
  return !!(
    filters.value.vessel_id ||
    filters.value.status ||
    filters.value.date_from ||
    filters.value.date_to ||
    filters.value.search
  );
});

// Methods
const fetchTrips = async (pageNumber = 1) => {
  loading.value = true;
  
  try {
    const params = new URLSearchParams({
      page: pageNumber.toString(),
      per_page: pagination.value.per_page.toString(),
    });
    
    if (filters.value.vessel_id) params.append('vessel_id', filters.value.vessel_id);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.date_from) params.append('date_from', filters.value.date_from);
    if (filters.value.date_to) params.append('date_to', filters.value.date_to);
    if (filters.value.search) params.append('search', filters.value.search);
    
    const response = await fetch(`/api/trips?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (!response.ok) throw new Error('Failed to fetch trips');
    
    const result = await response.json();
    trips.value = result.data;
    pagination.value = {
      current_page: result.current_page,
      last_page: result.last_page,
      per_page: result.per_page,
      total: result.total,
    };
  } catch (error) {
    console.error('Failed to fetch trips:', error);
    toast.error('Failed to load trips', 'Please try again.');
  } finally {
    loading.value = false;
  }
};

const clearFilters = () => {
  filters.value = {
    vessel_id: '',
    status: '',
    date_from: '',
    date_to: '',
    search: '',
  };
  fetchTrips(1);
};

const confirmDelete = (trip: Trip) => {
  selectedTrip.value = trip;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  if (!selectedTrip.value) return;
  
  deleting.value = true;
  
  try {
    const response = await fetch(`/api/trips/${selectedTrip.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Failed to delete trip');
    }
    
    toast.success('Trip deleted', 'Trip has been removed successfully.');
    showDeleteModal.value = false;
    fetchTrips(pagination.value.current_page);
  } catch (error: any) {
    console.error('Failed to delete trip:', error);
    toast.error('Delete failed', error.message || 'Please try again.');
  } finally {
    deleting.value = false;
  }
};

// Note: formatDate and formatCurrency are imported from utils

const getStatusBadgeClasses = (status: string): string => {
  switch (status) {
    case 'DRAFT':
      return 'bg-muted text-muted-foreground border-border';
    case 'ONGOING':
      return 'bg-warning/10 text-warning border-warning/20';
    case 'CLOSED':
      return 'bg-success/10 text-success border-success/20';
    default:
      return 'bg-muted text-muted-foreground border-border';
  }
};

const getDayOfWeekDisplay = (dayCode: string | null): string => {
  if (!dayCode) return '—';
  const days: Record<string, string> = {
    SAT: 'Saturday',
    SUN: 'Sunday',
    MON: 'Monday',
    TUE: 'Tuesday',
    WED: 'Wednesday',
    THU: 'Thursday',
    FRI: 'Friday',
  };
  return days[dayCode] || dayCode;
};

onMounted(() => {
  fetchTrips();
});
</script>

<template>
  <Head title="Trips" />
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Waves :size="28" class="text-primary" />
          <div>
            <h1 class="text-3xl font-bold text-foreground">Trips</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Manage fishing trip details and assignments
            </p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm space-y-4 overflow-visible">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <!-- Search -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-foreground mb-2">
              <MagnifyingGlassIcon class="w-4 h-4 inline-block mr-1" />
              Search
            </label>
            <div class="relative">
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by vessel or week..."
                class="w-full pl-10 pr-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
                @keyup.enter="fetchTrips(1)"
              />
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 w-5 h-5 text-muted-foreground" />
            </div>
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Status
            </label>
            <select
              v-model="filters.status"
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
              @change="fetchTrips(1)"
            >
              <option value="">All Statuses</option>
              <option value="DRAFT">Draft</option>
              <option value="ONGOING">Ongoing</option>
              <option value="CLOSED">Closed</option>
            </select>
          </div>

          <!-- Date From -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              <Calendar :size="16" class="inline-block mr-1" />
              Date From
            </label>
            <Datepicker
              v-model="filters.date_from"
              placeholder="Select start date"
              @change="fetchTrips(1)"
            />
          </div>

          <!-- Date To -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              <Calendar :size="16" class="inline-block mr-1" />
              Date To
            </label>
            <Datepicker
              v-model="filters.date_to"
              placeholder="Select end date"
              @change="fetchTrips(1)"
            />
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex items-center justify-between pt-2">
          <div class="text-sm text-muted-foreground">
            <span v-if="hasActiveFilters">
              Showing {{ filteredTripsCount }} filtered {{ filteredTripsCount === 1 ? 'trip' : 'trips' }}
            </span>
            <span v-else>
              Showing all {{ filteredTripsCount }} {{ filteredTripsCount === 1 ? 'trip' : 'trips' }}
            </span>
          </div>
          <button
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg hover:bg-accent/50 transition-all"
          >
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Trips Table -->
      <div class="glass-card rounded-xl border border-border/50 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Day
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  <Ship :size="14" class="inline-block mr-1" /> Vessel
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  <Calendar :size="14" class="inline-block mr-1" /> Week
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Fishing Day
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Total Sales
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-card divide-y divide-border">
              <!-- Loading State -->
              <tr v-if="loading">
                <td colspan="8" class="px-6 py-12 text-center">
                  <div class="flex items-center justify-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <span class="ml-3 text-muted-foreground">Loading trips...</span>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-else-if="trips.length === 0">
                <td colspan="8" class="px-6 py-12 text-center">
                  <Waves :size="48" class="mx-auto text-muted-foreground" />
                  <h3 class="mt-2 text-sm font-medium text-foreground">No trips found</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    {{ hasActiveFilters ? 'Try adjusting your filters.' : 'Trips are created automatically when you create a weekly sheet.' }}
                  </p>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr
                v-else
                v-for="trip in trips"
                :key="trip.id"
                class="hover:bg-accent/50 transition-colors"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-foreground">{{ formatDate(trip.date) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-foreground">{{ getDayOfWeekDisplay(trip.day_of_week) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-foreground">{{ trip.vessel.name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="trip.weekly_sheet" class="text-sm text-muted-foreground">
                    {{ formatDate(trip.weekly_sheet.week_start) }} - {{ formatDate(trip.weekly_sheet.week_end) }}
                  </div>
                  <div v-else class="text-sm text-muted-foreground italic">No week assigned</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                    :class="getStatusBadgeClasses(trip.status)"
                  >
                    {{ trip.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    v-if="trip.is_fishing_day"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success border border-success/20"
                  >
                    <CheckCircle :size="14" />
                    Yes
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground border border-border"
                  >
                    <XCircle :size="14" />
                    No
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-foreground">
                  {{ formatCurrency(trip.total_sales || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <a
                    :href="`/trips/${trip.id}`"
                    class="text-primary hover:text-primary/80 transition-colors inline-block"
                    title="View Trip"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </a>
                  
                  <button
                    v-if="trip.status === 'DRAFT' && user?.role === 'OWNER'"
                    @click="confirmDelete(trip)"
                    class="text-destructive hover:text-destructive/80 transition-colors"
                    title="Delete Trip"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="!loading && trips.length > 0" class="bg-muted/30 px-6 py-4 flex items-center justify-between border-t border-border">
          <div class="text-sm text-muted-foreground">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </div>
          <div class="flex gap-2">
            <button
              @click="fetchTrips(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1.5 text-sm border border-border rounded-lg hover:bg-accent/50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="fetchTrips(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1.5 text-sm border border-border rounded-lg hover:bg-accent/50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Delete Trip"
      :message="`Are you sure you want to delete this trip for <strong>${selectedTrip?.vessel.name}</strong> on <strong>${selectedTrip ? formatDate(selectedTrip.date) : ''}</strong>?<br><br>This action cannot be undone.`"
      confirm-text="Delete Trip"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </MainLayout>
</template>
