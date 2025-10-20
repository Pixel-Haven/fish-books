<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ToastContainer from '@/components/ToastContainer.vue';
import {
  HomeIcon,
  Cog6ToothIcon,
  ChevronDownIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  BellIcon,
} from '@heroicons/vue/24/outline';
import { Users, Ship, Fish, Waves, CalendarDays } from 'lucide-vue-next';

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
const isOwner = computed(() => user.value?.role === 'OWNER');
const isManager = computed(() => user.value?.role === 'MANAGER');

const sidebarOpen = ref(true);
const userMenuOpen = ref(false);

const navigation = computed(() => [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: HomeIcon,
    current: route().current('dashboard'),
  },
  {
    name: 'Crew Members',
    href: '/crew-members',
    icon: Users,
    current: route().current('crew-members.*'),
  },
  {
    name: 'Vessels',
    href: '/vessels',
    icon: Ship,
    current: route().current('vessels.*'),
    ownerOnly: true,
  },
  {
    name: 'Fish Types',
    href: '/fish-types',
    icon: Fish,
    current: route().current('fish-types.*'),
    ownerOnly: true,
  },
  {
    name: 'Trips',
    href: '/trips',
    icon: Waves,
    current: route().current('trips.*'),
  },
  {
    name: 'Weekly Sheets',
    href: '/weekly-sheets',
    icon: CalendarDays,
    current: route().current('weekly-sheets.*'),
    ownerOnly: true,
  },
]);

const filteredNavigation = computed(() => 
  navigation.value.filter(item => !item.ownerOnly || isOwner.value)
);

const logout = () => {
  router.post('/logout');
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-background via-muted/20 to-background">
    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
      class="bg-sidebar border-r border-sidebar-border shadow-xl"
    >
      <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-sidebar-border bg-gradient-to-r from-primary to-secondary">
          <div class="flex items-center space-x-3">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2c-1.5 0-2.8.4-4 1.2L5 6H2v4h1.3l1.5 3-1.5 3H2v4h3l3 2.8c1.2.8 2.5 1.2 4 1.2s2.8-.4 4-1.2l3-2.8h3v-4h-1.3l-1.5-3 1.5-3H22V6h-3l-3-2.8C14.8 2.4 13.5 2 12 2zm0 2c1 0 1.9.3 2.7.8L17.3 7H20v2h-1.2l-1.8 4 1.8 4H20v2h-2.7l-2.6 2.2c-.8.5-1.7.8-2.7.8s-1.9-.3-2.7-.8L6.7 19H4v-2h1.2l1.8-4-1.8-4H4V7h2.7l2.6-2.2C10.1 4.3 11 4 12 4zm2 6c.6 0 1 .4 1 1s-.4 1-1 1-1-.4-1-1 .4-1 1-1z"/>
            </svg>
            <span class="text-xl font-bold text-white">Fish Books</span>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
          <a
            v-for="item in filteredNavigation"
            :key="item.name"
            :href="item.href"
            :class="[
              item.current
                ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                : 'text-sidebar-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground',
              'group flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200'
            ]"
          >
            <component
              :is="item.icon"
              class="mr-3 h-5 w-5 flex-shrink-0" 
              :class="item.current ? 'text-primary' : 'text-sidebar-foreground/70 group-hover:text-primary'"
            />
            {{ item.name }}
          </a>
        </nav>

        <!-- User Profile -->
        <div class="border-t border-sidebar-border bg-sidebar">
          <div class="px-4 py-4">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold">
                  {{ user?.name?.charAt(0) || 'U' }}
                </div>
              </div>
              <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-sidebar-foreground">{{ user?.name || 'User' }}</p>
                <p class="text-xs text-muted-foreground">{{ user?.role || 'GUEST' }}</p>
              </div>
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="ml-2 p-1 rounded-md hover:bg-sidebar-accent transition-colors"
              >
                <ChevronDownIcon class="h-5 w-5 text-sidebar-foreground/70" />
              </button>
            </div>
            
            <!-- User Menu Dropdown -->
            <div v-if="userMenuOpen" class="mt-3 space-y-1">
              <button
                @click="logout"
                class="w-full flex items-center px-4 py-2 text-sm text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
              >
                <ArrowRightOnRectangleIcon class="mr-3 h-5 w-5" />
                Sign out
              </button>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div 
      :class="[
        'transition-all duration-300',
        sidebarOpen ? 'pl-64' : 'pl-0'
      ]"
    >
      <!-- Top Bar -->
      <header class="sticky top-0 z-40 bg-card/95 backdrop-blur-sm border-b border-border shadow-sm">
        <div class="flex items-center justify-between h-16 px-6">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg hover:bg-accent transition-colors"
          >
            <Bars3Icon class="h-6 w-6 text-foreground" />
          </button>

          <div class="flex items-center space-x-4">
            <!-- Notifications (placeholder) -->
            <button class="p-2 rounded-lg hover:bg-accent transition-colors relative">
              <BellIcon class="h-6 w-6 text-foreground" />
              <span class="absolute top-1 right-1 w-2 h-2 bg-destructive rounded-full"></span>
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <slot />
      </main>
    </div>

    <!-- Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script lang="ts">
// Helper function to check current route
function route() {
  return {
    current: (name: string) => {
      const currentPath = window.location.pathname;
      if (name === 'dashboard') return currentPath === '/dashboard';
      if (name.endsWith('.*')) {
        const baseName = name.replace('.*', '');
        return currentPath.startsWith(`/${baseName}`);
      }
      return currentPath === name;
    }
  };
}
</script>

<style scoped>
/* Custom scrollbar for sidebar */
nav::-webkit-scrollbar {
  width: 6px;
}

nav::-webkit-scrollbar-track {
  background: transparent;
}

nav::-webkit-scrollbar-thumb {
  background: var(--sidebar-border);
  border-radius: 3px;
}

nav::-webkit-scrollbar-thumb:hover {
  background: var(--sidebar-ring);
}
</style>
