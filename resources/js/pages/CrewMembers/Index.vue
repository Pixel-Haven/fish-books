<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Users, UserPlus } from 'lucide-vue-next';

interface CrewMember {
  id: string;
  name: string;
  id_card_no: string;
  bank_name?: string;
  bank_account_no?: string;
  phone?: string;
  active: boolean;
  created_at: string;
}

interface PaginatedResponse {
  data: CrewMember[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const crewMembers = ref<CrewMember[]>([]);
const pagination = ref<Omit<PaginatedResponse, 'data'>>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

const toast = useToast();

const loading = ref(true);
const searchQuery = ref('');
const activeFilter = ref<'all' | 'active' | 'inactive'>('all');
const showDeleteModal = ref(false);
const selectedMember = ref<CrewMember | null>(null);
const deleting = ref(false);
const showForm = ref(false);

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchCrewMembers = async () => {
  loading.value = true;
  
  const params = new URLSearchParams();
  if (searchQuery.value) params.append('search', searchQuery.value);
  if (activeFilter.value !== 'all') {
    params.append('active', activeFilter.value === 'active' ? '1' : '0');
  }
  params.append('page', pagination.value.current_page.toString());
  
  try {
    const response = await fetch(`/api/crew-members?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
    });
    
    const data: PaginatedResponse = await response.json();
    crewMembers.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    };
  } catch (error) {
    console.error('Failed to fetch crew members:', error);
  } finally {
    loading.value = false;
  }
};

const confirmDelete = (member: CrewMember) => {
  selectedMember.value = member;
  showDeleteModal.value = true;
};

const deleteMember = async () => {
  if (!selectedMember.value) return;
  
  deleting.value = true;
  
  try {
    const response = await fetch(`/api/crew-members/${selectedMember.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (response.ok) {
      toast.success('Crew member deleted', `${selectedMember.value.name} has been removed successfully.`);
      showDeleteModal.value = false;
      selectedMember.value = null;
      fetchCrewMembers();
    } else {
      toast.error('Delete failed', 'Unable to delete crew member. Please try again.');
    }
  } catch (error) {
    console.error('Failed to delete crew member:', error);
    toast.error('Delete failed', 'An error occurred while deleting the crew member.');
  } finally {
    deleting.value = false;
  }
};

const goToPage = (page: number) => {
  pagination.value.current_page = page;
  fetchCrewMembers();
};

onMounted(() => {
  fetchCrewMembers();
});
</script>

<template>
  <Head title="Crew Members" />
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Users :size="28" class="text-primary" />
          <div>
            <h1 class="text-3xl font-bold text-foreground">Crew Members</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Manage your fishing crew members and their details
            </p>
          </div>
        </div>
        <button
          @click="router.visit('/crew-members/create')"
          class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          Add Crew Member
        </button>
      </div>

      <!-- Filters Card -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <label for="search" class="block text-sm font-medium text-foreground mb-2">
              Search
            </label>
            <div class="relative">
              <input
                id="search"
                v-model="searchQuery"
                @input="fetchCrewMembers"
                type="text"
                placeholder="Search by name or ID card..."
                class="w-full pl-10 pr-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
              />
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
            </div>
          </div>

          <!-- Status Filter -->
          <div>
            <label for="status" class="block text-sm font-medium text-foreground mb-2">
              Status
            </label>
            <select
              id="status"
              v-model="activeFilter"
              @change="fetchCrewMembers"
              class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
            >
              <option value="all">All Members</option>
              <option value="active">Active Only</option>
              <option value="inactive">Inactive Only</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Crew Members Table -->
      <div class="glass-card rounded-xl border border-border/50 shadow-lg overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
          <p class="mt-4 text-muted-foreground">Loading crew members...</p>
        </div>

        <div v-else-if="crewMembers.length === 0" class="p-12 text-center">
          <Users :size="48" class="mx-auto text-muted-foreground" />
          <h3 class="mt-4 text-lg font-medium text-foreground">No crew members found</h3>
          <p class="mt-2 text-sm text-muted-foreground">Get started by adding your first crew member.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-border">
          <thead class="bg-muted/50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Name
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                ID Card
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Bank Details
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Phone
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-card divide-y divide-border">
            <tr v-for="member in crewMembers" :key="member.id" class="hover:bg-accent/50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold">
                      {{ member.name.charAt(0) }}
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-foreground">{{ member.name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-foreground">{{ member.id_card_no }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-foreground">
                  <div v-if="member.bank_name">{{ member.bank_name }}</div>
                  <div v-if="member.bank_account_no" class="text-xs text-muted-foreground">{{ member.bank_account_no }}</div>
                  <span v-if="!member.bank_name" class="text-muted-foreground">—</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                {{ member.phone || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    member.active
                      ? 'bg-success/10 text-success border border-success/20'
                      : 'bg-muted text-muted-foreground border border-border'
                  ]"
                >
                  {{ member.active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="router.visit(`/crew-members/${member.id}/edit`)"
                    class="text-primary hover:text-primary/80 p-2 rounded-lg hover:bg-accent transition-colors"
                    title="Edit"
                  >
                    <PencilIcon class="h-5 w-5" />
                  </button>
                  <button
                    @click="confirmDelete(member)"
                    class="text-destructive hover:text-destructive/80 p-2 rounded-lg hover:bg-destructive/10 transition-colors"
                    title="Delete"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="bg-muted/30 px-6 py-4 flex items-center justify-between border-t border-border">
          <div class="text-sm text-muted-foreground">
            Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of 
            {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-border rounded-lg hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            <button
              v-for="page in pagination.last_page"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1 border rounded-lg transition-colors',
                page === pagination.current_page
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'border-border hover:bg-accent'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-border rounded-lg hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
      title="Delete Crew Member"
      :message="`Are you sure you want to delete <strong>${selectedMember?.name}</strong>? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      :loading="deleting"
      @confirm="deleteMember"
      @cancel="showDeleteModal = false"
    />
  </MainLayout>
</template>
