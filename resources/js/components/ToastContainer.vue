<script setup lang="ts">
import { useToast } from '@/utils/toast';
import type { Toast } from '@/utils/toast';

const { toasts, remove } = useToast();

const getIcon = (type: Toast['type']) => {
  switch (type) {
    case 'success':
      return {
        path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        class: 'text-success',
      };
    case 'error':
      return {
        path: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        class: 'text-destructive',
      };
    case 'warning':
      return {
        path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        class: 'text-warning',
      };
    case 'info':
      return {
        path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        class: 'text-accent',
      };
  }
};

const getBgClass = (type: Toast['type']) => {
  switch (type) {
    case 'success':
      return 'bg-success/10 border-success/20';
    case 'error':
      return 'bg-destructive/10 border-destructive/20';
    case 'warning':
      return 'bg-warning/10 border-warning/20';
    case 'info':
      return 'bg-accent/10 border-accent/20';
  }
};
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] space-y-3 pointer-events-none">
      <TransitionGroup
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-start gap-3 p-4 rounded-lg border shadow-lg backdrop-blur-lg',
            'min-w-[320px] max-w-md',
            getBgClass(toast.type)
          ]"
        >
          <div class="flex-shrink-0">
            <svg
              :class="['h-6 w-6', getIcon(toast.type).class]"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                :d="getIcon(toast.type).path"
              />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-foreground">
              {{ toast.title }}
            </p>
            <p v-if="toast.message" class="mt-1 text-sm text-muted-foreground">
              {{ toast.message }}
            </p>
          </div>
          <button
            @click="remove(toast.id)"
            class="flex-shrink-0 text-muted-foreground hover:text-foreground transition-colors"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
