<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => {
      // Reset password field on finish (success or error)
      form.password = '';
    },
  });
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gradient-to-br from-ocean-deep via-ocean-medium to-ocean-light">
    <!-- Animated ocean background -->
    <div class="absolute inset-0 overflow-hidden">
      <!-- Waves -->
      <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-ocean-accent/20 to-transparent animate-pulse" />
      <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-ocean-foam/10 to-transparent animate-pulse" style="animation-delay: 0.5s;" />
      
      <!-- Floating particles (fish, bubbles effect) -->
      <div class="absolute inset-0">
        <div v-for="i in 20" :key="i" 
             class="absolute rounded-full bg-white/5"
             :style="{
               width: `${Math.random() * 8 + 2}px`,
               height: `${Math.random() * 8 + 2}px`,
               left: `${Math.random() * 100}%`,
               top: `${Math.random() * 100}%`,
               animation: `float ${Math.random() * 10 + 10}s infinite ease-in-out`,
               animationDelay: `${Math.random() * 5}s`
             }"
        />
      </div>
    </div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md px-6">
      <div class="glass-card rounded-2xl p-8 shadow-2xl backdrop-blur-xl border border-white/20">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
          <div class="inline-block">
            <!-- Fish Icon -->
            <svg class="w-16 h-16 mx-auto mb-4 text-ocean-foam" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2c-1.5 0-2.8.4-4 1.2L5 6H2v4h1.3l1.5 3-1.5 3H2v4h3l3 2.8c1.2.8 2.5 1.2 4 1.2s2.8-.4 4-1.2l3-2.8h3v-4h-1.3l-1.5-3 1.5-3H22V6h-3l-3-2.8C14.8 2.4 13.5 2 12 2zm0 2c1 0 1.9.3 2.7.8L17.3 7H20v2h-1.2l-1.8 4 1.8 4H20v2h-2.7l-2.6 2.2c-.8.5-1.7.8-2.7.8s-1.9-.3-2.7-.8L6.7 19H4v-2h1.2l1.8-4-1.8-4H4V7h2.7l2.6-2.2C10.1 4.3 11 4 12 4zm2 6c.6 0 1 .4 1 1s-.4 1-1 1-1-.4-1-1 .4-1 1-1z"/>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-white mb-2">Fish Books</h1>
          <p class="text-ocean-foam/80 text-sm">Fishing Crew Management System</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-white/90 mb-2">
              Email Address
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              :disabled="form.processing"
              class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-ocean-accent focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              placeholder="owner@hushiyaaru.com"
            />
            <p v-if="form.errors.email" class="mt-2 text-sm text-coral">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-white/90 mb-2">
              Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              :disabled="form.processing"
              class="w-full px-4 py-3 rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-ocean-accent focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="mt-2 text-sm text-coral">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              :disabled="form.processing"
              class="h-4 w-4 rounded border-white/20 bg-white/10 text-ocean-accent focus:ring-ocean-accent focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <label for="remember" class="ml-2 block text-sm text-white/80">
              Remember me
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3 px-4 bg-ocean-accent hover:bg-ocean-light text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
          >
            <span v-if="!form.processing">Sign In</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Signing in...
            </span>
          </button>
        </form>

        <!-- Test Credentials -->
        <div class="mt-8 pt-6 border-t border-white/10">
          <p class="text-xs text-center text-white/60 mb-3">Test Credentials:</p>
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
              <p class="text-white/80 font-medium mb-1">Owner</p>
              <p class="text-white/60 font-mono">owner@hushiyaaru.com</p>
              <p class="text-white/60 font-mono">owner123</p>
            </div>
            <div class="bg-white/5 rounded-lg p-3 border border-white/10">
              <p class="text-white/80 font-medium mb-1">Manager</p>
              <p class="text-white/60 font-mono">manager@hushiyaaru.com</p>
              <p class="text-white/60 font-mono">manager123</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center mt-6 text-white/50 text-sm">
        © 2025 Fish Books. All rights reserved.
      </p>
    </div>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% {
    transform: translateY(0) translateX(0);
  }
  50% {
    transform: translateY(-20px) translateX(10px);
  }
}

/* Custom color classes for our ocean theme */
.bg-ocean-deep {
  background-color: var(--ocean-deep, #0A2540);
}

.bg-ocean-medium {
  background-color: var(--ocean-medium, #1B4965);
}

.bg-ocean-light {
  background-color: var(--ocean-light, #2C6E8C);
}

.bg-ocean-accent {
  background-color: var(--ocean-accent, #5FA8D3);
}

.bg-ocean-foam {
  background-color: var(--ocean-foam, #CAE9FF);
}

.text-ocean-foam {
  color: var(--ocean-foam, #CAE9FF);
}

.text-coral {
  color: var(--coral, #FF6B6B);
}

.focus\:ring-ocean-accent:focus {
  --tw-ring-color: var(--ocean-accent, #5FA8D3);
}

.hover\:bg-ocean-light:hover {
  background-color: var(--ocean-light, #2C6E8C);
}
</style>
