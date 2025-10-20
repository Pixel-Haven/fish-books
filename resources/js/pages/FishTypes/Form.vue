<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import { formatDate, formatCurrency } from '@/utils/functions';
import { ChevronLeftIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

interface Props {
  id?: string;
}

interface FishTypeRate {
  id: string;
  rate: number;
  effective_from: string;
  created_at: string;
}

const props = defineProps<Props>();

const form = ref({
  name: '',
  default_rate_per_kilo: null as number | null,
  is_active: true,
});

const rateHistory = ref<FishTypeRate[]>([]);
const errors = ref<Record<string, string>>({});
const loading = ref(false);
const submitting = ref(false);

const isEditMode = computed(() => !!props.id);
const pageTitle = computed(() => isEditMode.value ? 'Edit Fish Type' : 'Add New Fish Type');

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchFishType = async () => {
  if (!props.id) return;
  
  loading.value = true;
  
  try {
    const response = await fetch(`/api/fish-types/${props.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (response.ok) {
      const data = await response.json();
      form.value = {
        name: data.name || '',
        default_rate_per_kilo: data.current_rate || null,
        is_active: data.is_active ?? true,
      };
      rateHistory.value = data.rates || [];
    }
  } catch (error) {
    console.error('Failed to fetch fish type:', error);
  } finally {
    loading.value = false;
  }
};

const submit = async () => {
  submitting.value = true;
  errors.value = {};
  
  const url = isEditMode.value 
    ? `/api/fish-types/${props.id}` 
    : '/api/fish-types';
  const method = isEditMode.value ? 'PUT' : 'POST';
  
  try {
    const response = await fetch(url, {
      method,
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
        errors.value = { name: data.message || 'An error occurred' };
      }
      submitting.value = false;
      return;
    }
    
    // Success - redirect to list
    router.visit('/fish-types');
  } catch (error) {
    console.error('Failed to submit:', error);
    errors.value = { name: 'Failed to save fish type. Please try again.' };
    submitting.value = false;
  }
};

// Note: formatDate and formatCurrency are imported from utils

onMounted(() => {
  if (isEditMode.value) {
    fetchFishType();
  }
});
</script>

<template>
  <Head :title="pageTitle" />
  <MainLayout>
    <div class="max-w-5xl mx-auto px-6 space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-foreground">{{ pageTitle }}</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ isEditMode ? 'Update fish type and rate information' : 'Add a new fish type with initial rate' }}
          </p>
        </div>
        <a
          href="/fish-types"
          class="inline-flex items-center px-4 py-2 border border-input rounded-lg hover:bg-accent/50 transition-all"
        >
          <ChevronLeftIcon class="w-5 h-5 mr-2" />
          Back to Fish Types
        </a>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg space-y-6">
          <h2 class="text-xl font-semibold text-foreground">Basic Information</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Fish Type Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-foreground mb-2">
                Fish Type Name <span class="text-destructive">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                :class="[
                  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all',
                  errors.name ? 'border-destructive' : 'border-input bg-background'
                ]"
                placeholder="e.g., Yellowfin Tuna, Skipjack"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-destructive">{{ errors.name }}</p>
              <p class="mt-1 text-xs text-muted-foreground">
                Common fish types: Damaged, Proper, Quality, Other
              </p>
            </div>

            <!-- Current Rate -->
            <div>
              <label for="default_rate_per_kilo" class="block text-sm font-medium text-foreground mb-2">
                Current Rate (per kg) <span class="text-destructive">*</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2.5 text-muted-foreground text-sm">Rf</span>
                <input
                  id="default_rate_per_kilo"
                  v-model.number="form.default_rate_per_kilo"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  :class="[
                    'w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all',
                    errors.default_rate_per_kilo ? 'border-destructive' : 'border-input bg-background'
                  ]"
                  placeholder="0.00"
                />
              </div>
              <p v-if="errors.default_rate_per_kilo" class="mt-1 text-sm text-destructive">{{ errors.default_rate_per_kilo }}</p>
              <p v-if="isEditMode" class="mt-1 text-xs text-warning">
                ⚠️ Changing this rate will archive the current rate and create a new one.
              </p>
            </div>

            <!-- Active Status -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-foreground mb-2">
                Status
              </label>
              <div class="flex items-center">
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-muted rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-success"></div>
                  <span class="ml-3 text-sm font-medium text-foreground">
                    {{ form.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </label>
              </div>
              <p class="mt-1 text-xs text-muted-foreground">
                Inactive fish types won't appear in trip forms but remain in historical data.
              </p>
            </div>
          </div>
        </div>

        <!-- Rate History (Edit Mode Only) -->
        <div v-if="isEditMode && rateHistory.length > 0" class="glass-card rounded-xl p-6 border border-border/50 shadow-lg space-y-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-foreground">Rate History</h2>
            <span class="text-sm text-muted-foreground">
              {{ rateHistory.length }} rate change{{ rateHistory.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <div class="space-y-3">
            <div
              v-for="(rate, index) in rateHistory"
              :key="rate.id"
              class="flex items-center justify-between p-4 bg-muted/30 rounded-lg border border-border/30"
            >
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                  {{ rateHistory.length - index }}
                </div>
                <div>
                  <div class="text-sm font-semibold text-foreground">
                    {{ formatCurrency(rate.rate) }}
                  </div>
                  <div class="text-xs text-muted-foreground">
                    per kilogram
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-muted-foreground">
                  {{ formatDate(rate.effective_from) }}
                </div>
                <div v-if="index === 0" class="text-xs text-success font-medium">
                  Current Rate
                </div>
                <div v-else class="text-xs text-muted-foreground">
                  Archived
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-start p-4 bg-accent/5 rounded-lg border border-accent/20">
            <InformationCircleIcon class="w-5 h-5 text-accent mr-3 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-muted-foreground">
              <strong class="text-foreground">Historical rates are preserved</strong> to ensure accurate calculations
              for past trips. Changing the current rate will automatically archive it with today's date.
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4">
          <a
            href="/fish-types"
            class="px-6 py-2 border border-input rounded-lg hover:bg-accent/50 transition-all"
          >
            Cancel
          </a>
          <button
            type="submit"
            :disabled="submitting"
            class="px-6 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5 transition-all duration-200"
          >
            <span v-if="submitting" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>
              {{ isEditMode ? 'Update Fish Type' : 'Create Fish Type' }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>
