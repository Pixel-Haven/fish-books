<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import { useToast } from '@/utils/toast';
import { formatDate } from '@/utils/functions';
import Datepicker from '@/components/ui/datepicker.vue';
import { ChevronLeftIcon, InformationCircleIcon, BanknotesIcon } from '@heroicons/vue/24/outline';

// Types
interface Vessel {
  id: string;
  name: string;
  registration_no: string;
  capacity: number | null;
}

// State
const vessels = ref<Vessel[]>([]);
const loading = ref(true);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const toast = useToast();

// Form data
const form = ref({
  vessel_id: '',
  week_start: '',
  week_end: '',
  label: '',
  description: '',
});

// Helper for CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

// Get next Saturday's date
const getNextSaturday = (): string => {
  const today = new Date();
  const dayOfWeek = today.getDay();
  const daysUntilSaturday = (6 - dayOfWeek + 7) % 7 || 7;
  const nextSaturday = new Date(today);
  nextSaturday.setDate(today.getDate() + daysUntilSaturday);
  return nextSaturday.toISOString().split('T')[0];
};

// Get Friday date (6 days after week_start)
const getFridayDate = (saturdayDate: string): string => {
  if (!saturdayDate) return '';
  const saturday = new Date(saturdayDate);
  const friday = new Date(saturday);
  friday.setDate(saturday.getDate() + 6);
  return friday.toISOString().split('T')[0];
};

// Computed week end date
const computedWeekEnd = computed(() => {
  return getFridayDate(form.value.week_start);
});

// Watch week_start and auto-update week_end
const updateWeekEnd = () => {
  form.value.week_end = computedWeekEnd.value;
};

// Selected vessel details
const selectedVessel = computed(() => {
  if (!form.value.vessel_id) return null;
  return vessels.value.find(v => v.id === form.value.vessel_id) || null;
});

// Trip preview (6 days: Sat-Thu)
const tripPreview = computed(() => {
  if (!form.value.week_start) return [];
  
  const trips = [];
  const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
  const dayShorts = ['SAT', 'SUN', 'MON', 'TUE', 'WED', 'THU'];
  const startDate = new Date(form.value.week_start);
  
  for (let i = 0; i < 6; i++) {
    const tripDate = new Date(startDate);
    tripDate.setDate(startDate.getDate() + i);
    
    trips.push({
      day: days[i],
      dayShort: dayShorts[i],
      date: formatDate(tripDate, 'MMM D, YYYY'),
      isoDate: tripDate.toISOString().split('T')[0],
    });
  }
  
  return trips;
});

// Pay day (Friday)
const payDay = computed(() => {
  if (!form.value.week_start) return null;
  const friday = new Date(form.value.week_start);
  friday.setDate(friday.getDate() + 6);
  return formatDate(friday, 'dddd, MMM D, YYYY');
});

// Fetch vessels
const fetchVessels = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/vessels', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    const result = await response.json();
    vessels.value = result.data;
  } catch (error) {
    console.error('Failed to fetch vessels:', error);
    toast.error('Failed to load vessels', 'Please try again.');
  } finally {
    loading.value = false;
  }
};

// Get suggested week start date for selected vessel
const getSuggestedWeekStart = async () => {
  if (!form.value.vessel_id) return;
  
  try {
    const response = await fetch(`/api/vessels/${form.value.vessel_id}/next-week-start`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (response.ok) {
      const result = await response.json();
      form.value.week_start = result.suggested_date;
      updateWeekEnd();
    }
  } catch (error) {
    console.error('Failed to get suggested week start:', error);
    // Fallback to next Saturday
    form.value.week_start = getNextSaturday();
    updateWeekEnd();
  }
};

// Validate Saturday
const validateSaturday = (): boolean => {
  if (!form.value.week_start) {
    errors.value.week_start = 'Week start date is required';
    return false;
  }
  
  const date = new Date(form.value.week_start);
  if (date.getDay() !== 6) {
    errors.value.week_start = 'Week must start on a Saturday';
    return false;
  }
  
  delete errors.value.week_start;
  return true;
};

// Submit form
const submit = async () => {
  errors.value = {};
  
  // Validate
  if (!form.value.vessel_id) {
    errors.value.vessel_id = 'Please select a vessel';
    return;
  }
  
  if (!validateSaturday()) {
    return;
  }
  
  submitting.value = true;
  
  try {
    const response = await fetch('/api/weekly-sheets', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
      body: JSON.stringify(form.value),
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      if (data.errors) {
        errors.value = data.errors;
      } else {
        toast.error('Failed to create weekly sheet', data.message || 'Please try again.');
      }
      return;
    }
    
    toast.success('Weekly sheet created', '6 trips have been auto-generated for the week.');
    router.visit('/weekly-sheets');
  } catch (error) {
    console.error('Failed to create weekly sheet:', error);
    toast.error('Failed to create weekly sheet', 'Please try again.');
  } finally {
    submitting.value = false;
  }
};

// Cancel and go back
const cancel = () => {
  router.visit('/weekly-sheets');
};

onMounted(() => {
  fetchVessels();
  // Set default to next Saturday
  form.value.week_start = getNextSaturday();
  updateWeekEnd();
});
</script>

<template>
  <MainLayout>
    <div class="max-w-5xl mx-auto px-6 space-y-6">
      <!-- Page Header -->
      <div>
        <button
          @click="cancel"
          class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4 transition-colors"
        >
          <ChevronLeftIcon class="mr-1 h-4 w-4" />
          Back to Weekly Sheets
        </button>
        <h1 class="text-3xl font-bold text-foreground">Create New Weekly Sheet</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Set up a new fishing week. 6 trips (Saturday-Thursday) will be auto-generated.
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card rounded-xl p-12 border border-border/50 shadow-lg">
        <div class="flex items-center justify-center">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="submit" class="space-y-6">
        <!-- Basic Info Card -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg space-y-6 overflow-visible">
          <h2 class="text-xl font-semibold text-foreground">Basic Information</h2>

          <!-- Vessel Selection -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Vessel <span class="text-destructive">*</span>
            </label>
            <select
              v-model="form.vessel_id"
              @change="getSuggestedWeekStart"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
              :class="errors.vessel_id ? 'border-destructive' : 'border-input bg-background'"
            >
              <option value="">Select a vessel...</option>
              <option v-for="vessel in vessels" :key="vessel.id" :value="vessel.id">
                {{ vessel.name }} ({{ vessel.registration_no }})
              </option>
            </select>
            <p v-if="errors.vessel_id" class="mt-1 text-sm text-destructive">{{ errors.vessel_id }}</p>
            <p v-else-if="selectedVessel" class="mt-1 text-sm text-muted-foreground">
              Capacity: {{ selectedVessel.capacity || 'Not specified' }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Week Start (Saturday) -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Week Start (Saturday) <span class="text-destructive">*</span>
              </label>
              <Datepicker
                v-model="form.week_start"
                format="YYYY-MM-DD"
                placeholder="Select week start (Saturday)"
                @change="updateWeekEnd(); validateSaturday()"
                :class="errors.week_start ? 'border-destructive' : ''"
              />
              <p v-if="errors.week_start" class="mt-1 text-sm text-destructive">{{ errors.week_start }}</p>
              <p v-else class="mt-1 text-sm text-muted-foreground">
                Fishing weeks must start on Saturday
              </p>
            </div>

            <!-- Week End (Friday) - Auto-calculated -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Week End (Friday)
              </label>
              <Datepicker
                :model-value="computedWeekEnd"
                format="YYYY-MM-DD"
                placeholder="Auto-calculated"
                disabled
              />
              <p class="mt-1 text-sm text-muted-foreground">
                Auto-calculated (6 days after start)
              </p>
            </div>
          </div>

          <!-- Label (Optional) -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Label (Optional)
            </label>
            <input
              v-model="form.label"
              type="text"
              placeholder="e.g., Week 1 January 2025"
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
            />
            <p class="mt-1 text-sm text-muted-foreground">
              Friendly name for this week (auto-generated if left empty)
            </p>
          </div>

          <!-- Description (Optional) -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Description (Optional)
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Any notes or special instructions for this week..."
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Trip Preview Card -->
        <div v-if="tripPreview.length > 0" class="glass-card rounded-xl p-6 border border-border/50 shadow-lg space-y-4">
          <h2 class="text-xl font-semibold text-foreground">Trip Preview</h2>
          <p class="text-sm text-muted-foreground">
            The following 6 trips will be auto-generated with DRAFT status:
          </p>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="trip in tripPreview"
              :key="trip.dayShort"
              class="bg-muted/30 rounded-lg p-4 border border-border/30"
            >
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-sm">
                  {{ trip.dayShort.slice(0, 2) }}
                </div>
                <div>
                  <div class="text-sm font-semibold text-foreground">{{ trip.day }}</div>
                  <div class="text-xs text-muted-foreground">{{ trip.date }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pay Day Notice -->
          <div class="mt-4 bg-success/10 border border-success/20 rounded-lg p-4 flex items-start gap-3">
            <BanknotesIcon class="h-5 w-5 text-success mt-0.5 flex-shrink-0" />
            <div>
              <div class="text-sm font-medium text-success">Pay Day</div>
              <div class="text-sm text-success/80">
                {{ payDay }} - Crew will be paid after the week is finalized
              </div>
            </div>
          </div>
        </div>

        <!-- Info Box -->
        <div class="bg-accent/20 border border-accent rounded-lg p-4 flex items-start gap-3">
          <InformationCircleIcon class="h-5 w-5 text-accent mt-0.5 flex-shrink-0" />
          <div class="text-sm text-foreground">
            <p class="font-medium mb-1">What happens next?</p>
            <ul class="list-disc list-inside space-y-1 text-muted-foreground">
              <li>6 trips (Saturday-Thursday) will be created with DRAFT status</li>
              <li>Manager can then fill in trip details (crew, sales, expenses)</li>
              <li>Owner can mark non-fishing days (rest days)</li>
              <li>Friday is the pay day after the week is finalized</li>
            </ul>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <button
            type="button"
            @click="cancel"
            class="px-6 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground font-medium rounded-lg transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-6 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none flex items-center gap-2"
          >
            <svg v-if="submitting" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ submitting ? 'Creating...' : 'Create Weekly Sheet' }}</span>
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>
