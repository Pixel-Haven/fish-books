<script setup lang="ts">
import Modal from './Modal.vue';
import { ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

interface Props {
  show: boolean;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'warning' | 'info';
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  variant: 'danger',
  loading: false,
});

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

const iconComponent = {
  danger: ExclamationTriangleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
}[props.variant];

const iconBgClass = {
  danger: 'bg-destructive/10',
  warning: 'bg-warning/10',
  info: 'bg-accent/10',
}[props.variant];

const iconColorClass = {
  danger: 'text-destructive',
  warning: 'text-warning',
  info: 'text-accent',
}[props.variant];

const confirmButtonClass = {
  danger: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
  warning: 'bg-warning text-white hover:bg-warning/90',
  info: 'bg-accent text-white hover:bg-accent/90',
}[props.variant];
</script>

<template>
  <Modal :show="show" max-width="lg" :closeable="!loading" @close="emit('cancel')">
    <div class="bg-card px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div 
          :class="[
            'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10',
            iconBgClass
          ]"
        >
          <component :is="iconComponent" :class="['h-6 w-6', iconColorClass]" />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <h3 class="text-lg leading-6 font-medium text-foreground">
            {{ title }}
          </h3>
          <div class="mt-2">
            <p class="text-sm text-muted-foreground" v-html="message"></p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-muted/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
      <button
        @click="emit('confirm')"
        :disabled="loading"
        type="button"
        :class="[
          'w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2 font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto sm:text-sm transition-all',
          confirmButtonClass,
          loading ? 'opacity-50 cursor-not-allowed' : ''
        ]"
      >
        <svg 
          v-if="loading" 
          class="animate-spin -ml-1 mr-2 h-4 w-4" 
          xmlns="http://www.w3.org/2000/svg" 
          fill="none" 
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ confirmText }}
      </button>
      <button
        @click="emit('cancel')"
        :disabled="loading"
        type="button"
        class="mt-3 w-full inline-flex justify-center rounded-lg border border-input shadow-sm px-4 py-2 bg-background text-foreground font-medium hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ cancelText }}
      </button>
    </div>
  </Modal>
</template>
