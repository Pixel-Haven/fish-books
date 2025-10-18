<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center"
                @click="handleBackdropClick"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                
                <!-- Modal Content -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div
                        v-if="isOpen"
                        class="relative bg-white dark:bg-grey-900 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-grey-200 dark:border-grey-800">
                            <h2 class="text-xl font-semibold text-grey-900 dark:text-grey-100">
                                <slot name="title">{{ title }}</slot>
                            </h2>
                            <button
                                @click="$emit('close')"
                                class="p-2 rounded-lg hover:bg-grey-100 dark:hover:bg-grey-800 transition-colors"
                            >
                                <XMarkIcon class="w-5 h-5 text-grey-500 dark:text-grey-400" />
                            </button>
                        </div>
                        
                        <!-- Body -->
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                            <slot></slot>
                        </div>
                        
                        <!-- Footer -->
                        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 p-6 border-t border-grey-200 dark:border-grey-800">
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline'

interface Props {
    isOpen: boolean
    title?: string
    closeOnBackdrop?: boolean
}

interface Emits {
    (e: 'close'): void
}

const props = withDefaults(defineProps<Props>(), {
    closeOnBackdrop: true
})

const emit = defineEmits<Emits>()

const handleBackdropClick = () => {
    if (props.closeOnBackdrop) {
        emit('close')
    }
}
</script>
