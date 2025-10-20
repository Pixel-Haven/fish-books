<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import { ChevronLeftIcon } from '@heroicons/vue/24/outline';

interface Props {
  id?: string;
}

const props = defineProps<Props>();

const form = ref({
  name: '',
  registration_no: '',
  capacity: null as number | null,
  notes: '',
  is_active: true,
});

const errors = ref<Record<string, string>>({});
const loading = ref(false);
const submitting = ref(false);

const isEditMode = computed(() => !!props.id);
const pageTitle = computed(() => isEditMode.value ? 'Edit Vessel' : 'Add New Vessel');

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchVessel = async () => {
  if (!props.id) return;
  
  loading.value = true;
  
  try {
    const response = await fetch(`/api/vessels/${props.id}`, {
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
        registration_no: data.registration_no || '',
        capacity: data.capacity || null,
        notes: data.notes || '',
        is_active: data.is_active ?? true,
      };
    }
  } catch (error) {
    console.error('Failed to fetch vessel:', error);
  } finally {
    loading.value = false;
  }
};

const submit = async () => {
  submitting.value = true;
  errors.value = {};
  
  const url = isEditMode.value 
    ? `/api/vessels/${props.id}` 
    : '/api/vessels';
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
    router.visit('/vessels');
  } catch (error) {
    console.error('Failed to submit:', error);
    errors.value = { name: 'Failed to save vessel. Please try again.' };
    submitting.value = false;
  }
};

onMounted(() => {
  if (isEditMode.value) {
    fetchVessel();
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
            {{ isEditMode ? 'Update vessel information' : 'Add a new fishing vessel to your fleet' }}
          </p>
        </div>
        <a
          href="/vessels"
          class="inline-flex items-center px-4 py-2 border border-input rounded-lg hover:bg-accent/50 transition-all"
        >
          <ChevronLeftIcon class="w-5 h-5 mr-2" />
          Back to Vessels
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
            <!-- Vessel Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-foreground mb-2">
                Vessel Name <span class="text-destructive">*</span>
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
                placeholder="e.g., Sea Breeze"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-destructive">{{ errors.name }}</p>
            </div>

            <!-- Registration Number -->
            <div>
              <label for="registration_no" class="block text-sm font-medium text-foreground mb-2">
                Registration No. <span class="text-destructive">*</span>
              </label>
              <input
                id="registration_no"
                v-model="form.registration_no"
                type="text"
                required
                :class="[
                  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all',
                  errors.registration_no ? 'border-destructive' : 'border-input bg-background'
                ]"
                placeholder="e.g., MV-2024-001"
              />
              <p v-if="errors.registration_no" class="mt-1 text-sm text-destructive">{{ errors.registration_no }}</p>
            </div>

            <!-- Capacity -->
            <div>
              <label for="capacity" class="block text-sm font-medium text-foreground mb-2">
                Capacity (tons)
              </label>
              <input
                id="capacity"
                v-model.number="form.capacity"
                type="number"
                step="0.01"
                min="0"
                :class="[
                  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all',
                  errors.capacity ? 'border-destructive' : 'border-input bg-background'
                ]"
                placeholder="e.g., 50.00"
              />
              <p v-if="errors.capacity" class="mt-1 text-sm text-destructive">{{ errors.capacity }}</p>
            </div>

            <!-- Active Status -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Status
              </label>
              <div class="flex items-center h-[42px]">
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
            </div>
          </div>
        </div>

        <!-- Additional Details -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg space-y-6">
          <h2 class="text-xl font-semibold text-foreground">Additional Details</h2>

          <!-- Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-foreground mb-2">
              Notes
            </label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              :class="[
                'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all',
                errors.notes ? 'border-destructive' : 'border-input bg-background'
              ]"
              placeholder="Any additional information about this vessel..."
            ></textarea>
            <p v-if="errors.notes" class="mt-1 text-sm text-destructive">{{ errors.notes }}</p>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4">
          <a
            href="/vessels"
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
              {{ isEditMode ? 'Update Vessel' : 'Create Vessel' }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>
