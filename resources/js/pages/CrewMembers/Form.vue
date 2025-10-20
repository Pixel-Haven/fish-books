<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import { ChevronLeftIcon, UserIcon, CreditCardIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

interface Props {
  id?: string;
}

const props = defineProps<Props>();

interface CrewMemberForm {
  name: string;
  id_card_no: string;
  bank_name: string;
  bank_account_no: string;
  phone: string;
  notes: string;
  active: boolean;
}

const form = ref<CrewMemberForm>({
  name: '',
  id_card_no: '',
  bank_name: '',
  bank_account_no: '',
  phone: '',
  notes: '',
  active: true,
});

const errors = ref<Partial<Record<keyof CrewMemberForm, string>>>({});
const loading = ref(false);
const submitting = ref(false);
const isEditMode = ref(!!props.id);
const pageTitle = computed(() => isEditMode.value ? 'Edit Crew Member' : 'Add Crew Member');

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchCrewMember = async () => {
  if (!props.id) return;
  
  loading.value = true;
  
  try {
    const response = await fetch(`/api/crew-members/${props.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
    });
    
    if (response.ok) {
      const data = await response.json();
      form.value = {
        name: data.name || '',
        id_card_no: data.id_card_no || '',
        bank_name: data.bank_name || '',
        bank_account_no: data.bank_account_no || '',
        phone: data.phone || '',
        notes: data.notes || '',
        active: data.active ?? true,
      };
    }
  } catch (error) {
    console.error('Failed to fetch crew member:', error);
  } finally {
    loading.value = false;
  }
};

const submit = async () => {
  submitting.value = true;
  errors.value = {};
  
  const url = isEditMode.value 
    ? `/api/crew-members/${props.id}` 
    : '/api/crew-members';
  const method = isEditMode.value ? 'PUT' : 'POST';
  
  try {
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
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
    router.visit('/crew-members');
  } catch (error) {
    console.error('Failed to save crew member:', error);
    errors.value = { name: 'An error occurred. Please try again.' };
    submitting.value = false;
  }
};

const cancel = () => {
  router.visit('/crew-members');
};

onMounted(() => {
  if (isEditMode.value) {
    fetchCrewMember();
  }
});
</script>

<template>
  <Head :title="pageTitle" />
  <MainLayout>
    <div class="max-w-5xl mx-auto px-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-foreground">
            {{ isEditMode ? 'Edit Crew Member' : 'Add Crew Member' }}
          </h1>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ isEditMode ? 'Update crew member information' : 'Add a new crew member to your fishing team' }}
          </p>
        </div>
        <button
          @click="cancel"
          class="inline-flex items-center px-4 py-2 border border-border hover:bg-accent text-foreground font-medium rounded-lg transition-colors"
        >
          <ChevronLeftIcon class="w-5 h-5 mr-2" />
          Back to List
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card rounded-xl p-12 border border-border/50 shadow-lg text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <p class="mt-4 text-muted-foreground">Loading crew member...</p>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information Card -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg">
          <h2 class="text-xl font-semibold text-foreground mb-6 flex items-center">
            <UserIcon class="w-6 h-6 mr-2 text-primary" />
            Basic Information
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div class="md:col-span-2">
              <label for="name" class="block text-sm font-medium text-foreground mb-2">
                Full Name <span class="text-destructive">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="Enter full name"
              />
              <p v-if="errors.name" class="mt-2 text-sm text-destructive">
                {{ errors.name }}
              </p>
            </div>

            <!-- ID Card Number -->
            <div>
              <label for="id_card_no" class="block text-sm font-medium text-foreground mb-2">
                ID Card Number <span class="text-destructive">*</span>
              </label>
              <input
                id="id_card_no"
                v-model="form.id_card_no"
                type="text"
                required
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="A123456"
              />
              <p v-if="errors.id_card_no" class="mt-2 text-sm text-destructive">
                {{ errors.id_card_no }}
              </p>
            </div>

            <!-- Phone -->
            <div>
              <label for="phone" class="block text-sm font-medium text-foreground mb-2">
                Phone Number
              </label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="7777777"
              />
              <p v-if="errors.phone" class="mt-2 text-sm text-destructive">
                {{ errors.phone }}
              </p>
            </div>
          </div>
        </div>

        <!-- Banking Information Card -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg">
          <h2 class="text-xl font-semibold text-foreground mb-6 flex items-center">
            <CreditCardIcon class="w-6 h-6 mr-2 text-primary" />
            Banking Details
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bank Name -->
            <div>
              <label for="bank_name" class="block text-sm font-medium text-foreground mb-2">
                Bank Name
              </label>
              <input
                id="bank_name"
                v-model="form.bank_name"
                type="text"
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="e.g., BML, Bank of Maldives"
              />
              <p v-if="errors.bank_name" class="mt-2 text-sm text-destructive">
                {{ errors.bank_name }}
              </p>
            </div>

            <!-- Bank Account Number -->
            <div>
              <label for="bank_account_no" class="block text-sm font-medium text-foreground mb-2">
                Account Number
              </label>
              <input
                id="bank_account_no"
                v-model="form.bank_account_no"
                type="text"
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                placeholder="7730001234567"
              />
              <p v-if="errors.bank_account_no" class="mt-2 text-sm text-destructive">
                {{ errors.bank_account_no }}
              </p>
            </div>
          </div>
        </div>

        <!-- Additional Information Card -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg">
          <h2 class="text-xl font-semibold text-foreground mb-6 flex items-center">
            <DocumentTextIcon class="w-6 h-6 mr-2 text-primary" />
            Additional Information
          </h2>

          <div class="space-y-6">
            <!-- Notes -->
            <div>
              <label for="notes" class="block text-sm font-medium text-foreground mb-2">
                Notes
              </label>
              <textarea
                id="notes"
                v-model="form.notes"
                rows="4"
                :disabled="submitting"
                class="w-full px-4 py-3 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all disabled:opacity-50 disabled:cursor-not-allowed resize-none"
                placeholder="Add any additional notes about this crew member..."
              ></textarea>
              <p v-if="errors.notes" class="mt-2 text-sm text-destructive">
                {{ errors.notes }}
              </p>
            </div>

            <!-- Active Status -->
            <div class="flex items-center space-x-3">
              <input
                id="active"
                v-model="form.active"
                type="checkbox"
                :disabled="submitting"
                class="h-5 w-5 rounded border-input bg-background text-primary focus:ring-primary focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
              />
              <label for="active" class="text-sm font-medium text-foreground cursor-pointer">
                Active crew member
              </label>
            </div>
            <p class="text-xs text-muted-foreground ml-8">
              Inactive crew members won't appear in trip assignments but their data will be preserved.
            </p>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm flex items-center justify-end space-x-4">
          <button
            type="button"
            @click="cancel"
            :disabled="submitting"
            class="px-6 py-3 border border-border hover:bg-accent text-foreground font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-6 py-3 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
          >
            <span v-if="!submitting">
              {{ isEditMode ? 'Update Crew Member' : 'Add Crew Member' }}
            </span>
            <span v-else class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isEditMode ? 'Updating...' : 'Adding...' }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </MainLayout>
</template>
