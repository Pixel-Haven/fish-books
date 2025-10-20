<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Ship, Anchor } from 'lucide-vue-next';

interface Vessel {
  id: string;
  name: string;
  registration_no: string;
  capacity?: number;
  notes?: string;
  is_active: boolean;
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

interface PaginatedResponse {
  data: Vessel[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const toast = useToast();

const vessels = ref<Vessel[]>([]);
const pagination = ref<Omit<PaginatedResponse, 'data'>>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

const loading = ref(true);
const searchQuery = ref('');
const activeFilter = ref<'all' | 'active' | 'inactive'>('all');
const showDeleteModal = ref(false);
const selectedVessel = ref<Vessel | null>(null);
const deleting = ref(false);

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchVessels = async () => {
  loading.value = true;
  
  const params = new URLSearchParams();
  if (searchQuery.value) params.append('search', searchQuery.value);
  if (activeFilter.value !== 'all') {
    params.append('is_active', activeFilter.value === 'active' ? '1' : '0');
  }
  params.append('page', pagination.value.current_page.toString());
  
  try {
    const response = await fetch(`/api/vessels?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    const data: PaginatedResponse = await response.json();
    vessels.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    };
  } catch (error) {
    console.error('Failed to fetch vessels:', error);
  } finally {
    loading.value = false;
  }
};

const confirmDelete = (vessel: Vessel) => {
  selectedVessel.value = vessel;
  showDeleteModal.value = true;
};

const deleteVessel = async () => {
  if (!selectedVessel.value) return;
  
  deleting.value = true;
  
  try {
    const response = await fetch(`/api/vessels/${selectedVessel.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
    });
    
    if (response.ok) {
      toast.success('Vessel deleted', `${selectedVessel.value.name} has been removed successfully.`);
      showDeleteModal.value = false;
      selectedVessel.value = null;
      fetchVessels();
    } else {
      toast.error('Delete failed', 'Unable to delete vessel. Please try again.');
    }
  } catch (error) {
    console.error('Failed to delete vessel:', error);
    toast.error('Delete failed', 'An error occurred while deleting the vessel.');
  } finally {
    deleting.value = false;
  }
};

const goToPage = (page: number) => {
  pagination.value.current_page = page;
  fetchVessels();
};

onMounted(() => {
  fetchVessels();
});
</script>

<template>
  <Head title="Vessels" />
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Ship :size="28" class="text-primary" />
          <div>
            <h1 class="text-3xl font-bold text-foreground">Vessels</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Manage your fishing vessels and their details
            </p>
          </div>
        </div>
        <a
          href="/vessels/create"
          class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          Add Vessel
        </a>
      </div>

      <!-- Search and Filters -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="fetchVessels"
              type="text"
              placeholder="Search vessels..."
              class="w-full pl-10 pr-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
            />
            <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
          </div>

          <!-- Active Filter -->
          <select
            v-model="activeFilter"
            @change="fetchVessels"
            class="px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
          >
            <option value="all">All Status</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive Only</option>
          </select>

          <!-- Stats -->
          <div class="flex items-center justify-end text-sm text-muted-foreground">
            <span class="font-medium">{{ pagination.total }}</span>
            <span class="ml-1">vessels total</span>
          </div>
        </div>
      </div>

      <!-- Vessels Table -->
      <div class="glass-card rounded-xl border border-border/50 shadow-lg overflow-hidden">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="vessels.length === 0" class="text-center py-12">
          <Ship :size="48" class="mx-auto text-muted-foreground" />
          <h3 class="mt-2 text-sm font-medium text-foreground">No vessels found</h3>
          <p class="mt-1 text-sm text-muted-foreground">Get started by adding a new vessel.</p>
        </div>

        <table v-else class="min-w-full divide-y divide-border">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Vessel Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Registration No.
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Capacity
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
              v-for="vessel in vessels"
              :key="vessel.id"
              class="hover:bg-accent/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-foreground">{{ vessel.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-muted-foreground">{{ vessel.registration_no }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-muted-foreground">
                  {{ vessel.capacity ? `${vessel.capacity} tons` : '—' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-if="vessel.is_active"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success border border-success/20"
                >
                  Active
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground border border-border"
                >
                  Inactive
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <a
                    :href="`/vessels/${vessel.id}/edit`"
                    class="text-primary hover:text-primary/80 p-2 rounded-lg hover:bg-accent transition-colors"
                    title="Edit"
                  >
                    <PencilIcon class="h-5 w-5" />
                  </a>
                  <button
                    @click="confirmDelete(vessel)"
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
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
          Showing page {{ pagination.current_page }} of {{ pagination.last_page }}
        </div>
        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-4 py-2 border border-input rounded-lg hover:bg-accent/50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            Previous
          </button>
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-4 py-2 border border-input rounded-lg hover:bg-accent/50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Delete Vessel"
      :message="`Are you sure you want to delete <strong>${selectedVessel?.name}</strong>? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      :loading="deleting"
      @confirm="deleteVessel"
      @cancel="showDeleteModal = false"
    />
  </MainLayout>
</template>
