<script setup lang="ts">
import { ref, computed } from 'vue';
import { useToast } from '@/utils/toast';

const props = defineProps<{
  trip: any;
}>();

const emit = defineEmits(['complete']);

const toast = useToast();
const saving = ref(false);

const form = ref({
  departure_time: props.trip.departure_time || '',
  return_time: props.trip.return_time || '',
  remarks: props.trip.remarks || '',
  weather_notes: props.trip.weather_notes || '',
  is_fishing_day: props.trip.is_fishing_day,
});

const isEditable = computed(() => ['DRAFT', 'ONGOING'].includes(props.trip.status));

const submit = async () => {
  saving.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
      body: JSON.stringify(form.value),
    });

    if (!response.ok) throw new Error('Failed to update trip');

    const data = await response.json();
    toast.success('Saved', 'Trip details updated');
    emit('complete', data.data);
  } catch (error) {
    console.error('Update error:', error);
    toast.error('Error', 'Failed to update trip details');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="glass-card p-6 rounded-xl border border-border/50 shadow-lg space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-foreground">Trip Basics</h2>
      <p class="text-sm text-muted-foreground">Configure the basic details for this trip.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Fishing Day Toggle -->
      <div class="flex items-center space-x-3 bg-muted/30 p-4 rounded-lg border border-border/50">
        <input
          id="is_fishing_day"
          v-model="form.is_fishing_day"
          type="checkbox"
          :disabled="!isEditable"
          class="h-5 w-5 text-primary border-gray-300 rounded focus:ring-primary"
        />
        <label for="is_fishing_day" class="flex flex-col cursor-pointer">
          <span class="text-sm font-medium text-foreground">Is Fishing Day?</span>
          <span class="text-xs text-muted-foreground">Uncheck if this was a rest day or maintenance day (no fishing).</span>
        </label>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Departure Time</label>
          <input
            v-model="form.departure_time"
            type="time"
            :disabled="!isEditable"
            class="w-full px-4 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none disabled:opacity-50"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Return Time</label>
          <input
            v-model="form.return_time"
            type="time"
            :disabled="!isEditable"
            class="w-full px-4 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none disabled:opacity-50"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-foreground mb-1">Weather Notes</label>
        <input
          v-model="form.weather_notes"
          type="text"
          placeholder="Sunny, Rough Seas, etc."
          :disabled="!isEditable"
          class="w-full px-4 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none disabled:opacity-50"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-foreground mb-1">Remarks</label>
        <textarea
          v-model="form.remarks"
          rows="3"
          placeholder="Any additional notes..."
          :disabled="!isEditable"
          class="w-full px-4 py-2 bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary focus:outline-none disabled:opacity-50 resize-none"
        ></textarea>
      </div>

      <div class="flex justify-end pt-4">
        <button
          v-if="isEditable"
          type="submit"
          :disabled="saving"
          class="px-6 py-2 bg-primary text-primary-foreground rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <span v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
          {{ saving ? 'Saving...' : 'Save & Continue' }}
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
    </form>
  </div>
</template>
