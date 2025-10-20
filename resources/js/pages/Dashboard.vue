<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';
import {
  UserGroupIcon,
  MapIcon,
  TruckIcon,
  BanknotesIcon,
  PlusIcon,
  ClipboardDocumentListIcon,
  UsersIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';

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

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

const stats = ref({
  totalCrewMembers: 0,
  activeTrips: 0,
  totalVessels: 0,
  pendingPayouts: 0,
});

const loading = ref(true);

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const fetchDashboardStats = async () => {
  loading.value = true;
  
  try {
    // Fetch crew members count
    const crewResponse = await fetch('/api/crew-members?per_page=1', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
    });
    if (crewResponse.ok) {
      const crewData = await crewResponse.json();
      stats.value.totalCrewMembers = crewData.total || 0;
    }

    // Fetch active trips
    const tripsResponse = await fetch('/api/trips?status=ONGOING&per_page=1', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
    });
    if (tripsResponse.ok) {
      const tripsData = await tripsResponse.json();
      stats.value.activeTrips = tripsData.total || 0;
    }

    // Fetch vessels count (if owner)
    if (user.value?.role === 'OWNER') {
      const vesselsResponse = await fetch('/api/vessels?per_page=1', {
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'include', // Include session cookies
      });
      if (vesselsResponse.ok) {
        const vesselsData = await vesselsResponse.json();
        stats.value.totalVessels = vesselsData.total || 0;
      }
    }
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboardStats();
});
</script>

<template>
  <Head title="Dashboard" />
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Welcome Header -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg bg-gradient-to-r from-primary/10 via-accent/10 to-primary/10">
        <h1 class="text-3xl font-bold text-foreground">
          Welcome back, {{ user?.name || 'User' }}! 🌊
        </h1>
        <p class="mt-2 text-muted-foreground">
          Here's an overview of your fishing operations.
        </p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Crew Members -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Crew Members</p>
              <p class="mt-2 text-3xl font-bold text-foreground">
                {{ loading ? '—' : stats.totalCrewMembers }}
              </p>
            </div>
            <div class="p-3 rounded-full bg-primary/10">
              <UserGroupIcon class="w-8 h-8 text-primary" />
            </div>
          </div>
          <div class="mt-4">
            <a href="/crew-members" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
              View all →
            </a>
          </div>
        </div>

        <!-- Active Trips -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Active Trips</p>
              <p class="mt-2 text-3xl font-bold text-foreground">
                {{ loading ? '—' : stats.activeTrips }}
              </p>
            </div>
            <div class="p-3 rounded-full bg-accent/10">
              <MapIcon class="w-8 h-8 text-accent" />
            </div>
          </div>
          <div class="mt-4">
            <a href="/trips" class="text-sm font-medium text-accent hover:text-accent/80 transition-colors">
              View all →
            </a>
          </div>
        </div>

        <!-- Vessels (Owner only) -->
        <div v-if="user?.role === 'OWNER'" class="glass-card rounded-xl p-6 border border-border/50 shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Vessels</p>
              <p class="mt-2 text-3xl font-bold text-foreground">
                {{ loading ? '—' : stats.totalVessels }}
              </p>
            </div>
            <div class="p-3 rounded-full bg-success/10">
              <TruckIcon class="w-8 h-8 text-success" />
            </div>
          </div>
          <div class="mt-4">
            <a href="/vessels" class="text-sm font-medium text-success hover:text-success/80 transition-colors">
              View all →
            </a>
          </div>
        </div>

        <!-- Pending Payouts (placeholder) -->
        <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Pending Payouts</p>
              <p class="mt-2 text-3xl font-bold text-foreground">
                {{ loading ? '—' : stats.pendingPayouts }}
              </p>
            </div>
            <div class="p-3 rounded-full bg-warning/10">
              <BanknotesIcon class="w-8 h-8 text-warning" />
            </div>
          </div>
          <div class="mt-4">
            <a href="/weekly-sheets" class="text-sm font-medium text-warning hover:text-warning/80 transition-colors">
              View all →
            </a>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="glass-card rounded-xl p-6 border border-border/50 shadow-lg">
        <h2 class="text-xl font-semibold text-foreground mb-4 flex items-center">
          <LightBulbIcon class="w-6 h-6 mr-2 text-primary" />
          Quick Actions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <a
            href="/crew-members/create"
            class="flex items-center p-4 border border-border rounded-lg hover:bg-accent/50 hover:border-primary transition-all group"
          >
            <div class="p-2 rounded-lg bg-primary/10 group-hover:bg-primary/20 transition-colors">
              <UsersIcon class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
              <p class="font-medium text-foreground">Add Crew Member</p>
              <p class="text-sm text-muted-foreground">Add new crew to your team</p>
            </div>
          </a>

          <a
            href="/trips/create"
            class="flex items-center p-4 border border-border rounded-lg hover:bg-accent/50 hover:border-primary transition-all group"
          >
            <div class="p-2 rounded-lg bg-accent/10 group-hover:bg-accent/20 transition-colors">
              <PlusIcon class="w-6 h-6 text-accent" />
            </div>
            <div class="ml-4">
              <p class="font-medium text-foreground">New Trip</p>
              <p class="text-sm text-muted-foreground">Start a new fishing trip</p>
            </div>
          </a>

          <a
            v-if="user?.role === 'OWNER'"
            href="/weekly-sheets/create"
            class="flex items-center p-4 border border-border rounded-lg hover:bg-accent/50 hover:border-primary transition-all group"
          >
            <div class="p-2 rounded-lg bg-success/10 group-hover:bg-success/20 transition-colors">
              <ClipboardDocumentListIcon class="w-6 h-6 text-success" />
            </div>
            <div class="ml-4">
              <p class="font-medium text-foreground">New Weekly Sheet</p>
              <p class="text-sm text-muted-foreground">Create weekly payout sheet</p>
            </div>
          </a>
        </div>
      </div>

      <!-- Getting Started (if no data) -->
      <div v-if="!loading && stats.totalCrewMembers === 0" class="glass-card rounded-xl p-8 border border-border/50 shadow-lg text-center">
        <div class="max-w-md mx-auto">
          <LightBulbIcon class="mx-auto h-16 w-16 text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold text-foreground mb-2">Getting Started with Fish Books</h3>
          <p class="text-muted-foreground mb-6">
            Start by adding your crew members, vessels, and fish types to begin managing your fishing operations.
          </p>
          <div class="flex justify-center space-x-4">
            <a
              href="/crew-members/create"
              class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200"
            >
              Add Crew Members
            </a>
            <a
              v-if="user?.role === 'OWNER'"
              href="/vessels/create"
              class="inline-flex items-center px-6 py-3 bg-secondary hover:bg-secondary/90 text-secondary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
            >
              Add Vessels
            </a>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
