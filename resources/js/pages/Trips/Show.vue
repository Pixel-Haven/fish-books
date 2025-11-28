<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import { useToast } from '@/utils/toast';
import { formatDate } from '@/utils/functions';
import {
  ChevronLeftIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/solid';
import {
  ClipboardDocumentListIcon,
  UserGroupIcon,
  ReceiptPercentIcon,
  ShoppingBagIcon,
  BanknotesIcon,
  CheckBadgeIcon
} from '@heroicons/vue/24/outline';

// Import Wizard Steps
import Step1Basics from './WizardSteps/Step1Basics.vue';
import Step2Crew from './WizardSteps/Step2Crew.vue';
import Step3Bills from './WizardSteps/Step3Bills.vue';
import Step4Purchases from './WizardSteps/Step4Purchases.vue';
import Step5Expenses from './WizardSteps/Step5Expenses.vue';
import Step6Finalize from './WizardSteps/Step6Finalize.vue';

const props = defineProps<{
  id: string;
}>();

const toast = useToast();
const loading = ref(true);
const trip = ref<any>(null);
const currentStep = ref(1);
const calculations = ref<any>(null);

const steps = [
  { id: 1, name: 'Basics', icon: ClipboardDocumentListIcon },
  { id: 2, name: 'Crew', icon: UserGroupIcon },
  { id: 3, name: 'Sales & Bills', icon: ReceiptPercentIcon },
  { id: 4, name: 'Purchases', icon: ShoppingBagIcon },
  { id: 5, name: 'Expenses', icon: BanknotesIcon },
  { id: 6, name: 'Finalize', icon: CheckBadgeIcon },
];

const fetchTrip = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/api/trips/${props.id}`, {
      headers: { 'Accept': 'application/json' },
      credentials: 'include',
    });
    const data = await response.json();
    trip.value = data.trip;
    calculations.value = data.calculations;
  } catch (error) {
    console.error('Failed to load trip:', error);
    toast.error('Error', 'Failed to load trip details');
  } finally {
    loading.value = false;
  }
};

const handleStepComplete = async (stepData: any) => {
  // Refresh trip data to reflect changes
  await fetchTrip();

  // Advance to next step if not final
  if (currentStep.value < steps.length) {
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const goToStep = (step: number) => {
  // Allow going back, or going forward if previous steps are "complete" (simplified for now)
  if (step <= currentStep.value || trip.value?.status !== 'DRAFT') {
    currentStep.value = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(() => {
  fetchTrip();
});
</script>

<template>
  <Head title="Trip Wizard" />
  <MainLayout>
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
    </div>

    <div v-else-if="trip" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Header -->
      <div>
        <button
          @click="router.visit(`/weekly-sheets/${trip.weekly_sheet_id}`)"
          class="flex items-center text-sm text-muted-foreground hover:text-foreground mb-4 transition-colors"
        >
          <ChevronLeftIcon class="h-4 w-4 mr-1" />
          Back to Weekly Sheet
        </button>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-foreground flex items-center gap-3">
              Trip Details
              <span class="text-lg font-normal text-muted-foreground">
                {{ formatDate(trip.date) }} ({{ trip.day_of_week }})
              </span>
            </h1>
            <p class="text-muted-foreground mt-1">{{ trip.vessel.name }} • {{ trip.status }}</p>
          </div>

          <!-- Step Indicator -->
          <div class="hidden md:flex items-center gap-2 bg-card border border-border/50 p-2 rounded-lg">
            <span class="text-sm font-medium text-foreground">Step {{ currentStep }} of {{ steps.length }}:</span>
            <span class="text-sm text-primary">{{ steps[currentStep - 1].name }}</span>
          </div>
        </div>
      </div>

      <!-- Stepper -->
      <nav aria-label="Progress">
        <ol role="list" class="divide-y divide-border rounded-md border border-border md:flex md:divide-y-0">
          <li v-for="(step, stepIdx) in steps" :key="step.name" class="relative md:flex md:flex-1">
            <button
              @click="goToStep(step.id)"
              class="group flex w-full items-center"
              :disabled="step.id > currentStep && trip.status === 'DRAFT'"
            >
              <span class="flex items-center px-6 py-4 text-sm font-medium">
                <span
                  class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                  :class="[
                    step.id < currentStep ? 'border-primary bg-primary text-primary-foreground' :
                    step.id === currentStep ? 'border-primary text-primary' :
                    'border-muted text-muted-foreground group-hover:border-muted-foreground'
                  ]"
                >
                  <component
                    :is="step.id < currentStep ? CheckCircleIcon : step.icon"
                    class="h-6 w-6"
                  />
                </span>
                <span
                  class="ml-4 text-sm font-medium transition-colors"
                  :class="[
                    step.id <= currentStep ? 'text-foreground' : 'text-muted-foreground'
                  ]"
                >
                  {{ step.name }}
                </span>
              </span>
            </button>

            <!-- Arrow separator -->
            <div v-if="stepIdx !== steps.length - 1" class="hidden md:block absolute top-0 right-0 h-full w-5">
              <svg class="h-full w-full text-border" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentColor" stroke-linejoin="round" />
              </svg>
            </div>
          </li>
        </ol>
      </nav>

      <!-- Step Content -->
      <div class="min-h-[400px]">
        <Step1Basics v-if="currentStep === 1" :trip="trip" @complete="handleStepComplete" />
        <Step2Crew v-if="currentStep === 2" :trip="trip" @complete="handleStepComplete" />
        <Step3Bills v-if="currentStep === 3" :trip="trip" @complete="handleStepComplete" />
        <Step4Purchases v-if="currentStep === 4" :trip="trip" @complete="handleStepComplete" />
        <Step5Expenses v-if="currentStep === 5" :trip="trip" @complete="handleStepComplete" />
        <Step6Finalize
          v-if="currentStep === 6"
          :trip="trip"
          :calculations="calculations"
          @complete="handleStepComplete"
          @refresh="fetchTrip"
        />
      </div>
    </div>
  </MainLayout>
</template>
