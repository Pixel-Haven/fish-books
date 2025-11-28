<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useToast } from '@/utils/toast';
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  trip: any;
}>();

const emit = defineEmits(['complete']);

interface CrewAssignment {
  crew_member_id: string;
  role: string;
  helper_ratio: number;
}

interface CrewMember {
  id: string;
  name: string;
  id_card_number: string;
}

const toast = useToast();
const loading = ref(true);
const saving = ref(false);
const crewMembers = ref<CrewMember[]>([]);
const assignments = ref<CrewAssignment[]>([]);

const roles = ['BAITING', 'FISHING', 'CHUMMER', 'DIVING', 'HELPER', 'SPECIAL'];
const isEditable = computed(() => ['DRAFT', 'ONGOING'].includes(props.trip.status));

const fetchCrew = async () => {
  try {
    const response = await fetch('/api/crew-members?active=true', {
      headers: { 'Accept': 'application/json' },
      credentials: 'include',
    });
    const result = await response.json();
    crewMembers.value = result.data || [];

    // Initialize assignments from trip data if available
    if (props.trip.trip_assignments && props.trip.trip_assignments.length > 0) {
      assignments.value = props.trip.trip_assignments.map((a: any) => ({
        crew_member_id: a.crew_member_id,
        role: a.role,
        helper_ratio: Number(a.helper_ratio) || 1.0,
      }));
    } else {
      // Default: one empty row
      addAssignment();
    }
  } catch (error) {
    console.error('Failed to fetch crew:', error);
  } finally {
    loading.value = false;
  }
};

const addAssignment = () => {
  assignments.value.push({
    crew_member_id: '',
    role: 'FISHING',
    helper_ratio: 1.0,
  });
};

const removeAssignment = (index: number) => {
  assignments.value.splice(index, 1);
};

const submit = async () => {
  // Validate
  if (assignments.value.length === 0) {
    toast.error('Validation', 'At least one crew member is required');
    return;
  }

  const validAssignments = assignments.value.filter(a => a.crew_member_id);
  if (validAssignments.length === 0) {
    toast.error('Validation', 'Please select crew members');
    return;
  }

  // Check duplicates
  const ids = validAssignments.map(a => a.crew_member_id);
  if (new Set(ids).size !== ids.length) {
    toast.error('Validation', 'Duplicate crew members selected');
    return;
  }

  saving.value = true;
  try {
    const response = await fetch(`/api/trips/${props.trip.id}/assign-crew`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'include',
      body: JSON.stringify({ assignments: validAssignments }),
    });

    if (!response.ok) throw new Error('Failed to save crew');

    const data = await response.json();
    toast.success('Saved', 'Crew assignments updated');
    emit('complete', data.data);
  } catch (error) {
    console.error('Save error:', error);
    toast.error('Error', 'Failed to save crew assignments');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchCrew();
});
</script>

<template>
  <div class="glass-card p-6 rounded-xl border border-border/50 shadow-lg space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-foreground">Crew Assignments</h2>
        <p class="text-sm text-muted-foreground">Assign roles to crew members for this trip.</p>
      </div>
      <button
        v-if="isEditable"
        @click="addAssignment"
        class="inline-flex items-center px-3 py-1.5 bg-secondary text-secondary-foreground text-sm font-medium rounded-lg hover:bg-secondary/90 transition-colors"
      >
        <PlusIcon class="h-4 w-4 mr-1" />
        Add Crew
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <div class="space-y-3">
        <div v-for="(assignment, index) in assignments" :key="index" class="flex flex-col md:flex-row gap-3 items-start md:items-center bg-muted/20 p-3 rounded-lg border border-border/50">
          <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-muted-foreground mb-1 md:hidden">Crew Member</label>
            <select
              v-model="assignment.crew_member_id"
              :disabled="!isEditable"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
            >
              <option value="">Select Crew Member</option>
              <option v-for="crew in crewMembers" :key="crew.id" :value="crew.id">
                {{ crew.name }}
              </option>
            </select>
          </div>

          <div class="w-full md:w-40">
            <label class="block text-xs font-medium text-muted-foreground mb-1 md:hidden">Role</label>
            <select
              v-model="assignment.role"
              :disabled="!isEditable"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
            >
              <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
            </select>
          </div>

          <div class="w-full md:w-32" v-if="assignment.role === 'SPECIAL'">
            <label class="block text-xs font-medium text-muted-foreground mb-1 md:hidden">Helper Ratio</label>
            <input
              v-model="assignment.helper_ratio"
              type="number"
              step="0.1"
              min="0.1"
              max="2.0"
              :disabled="!isEditable"
              class="w-full px-3 py-2 bg-background border border-input rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
              placeholder="Ratio"
            />
          </div>

          <button
            v-if="isEditable"
            type="button"
            @click="removeAssignment(index)"
            class="p-2 text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
          >
            <TrashIcon class="h-5 w-5" />
          </button>
        </div>
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
