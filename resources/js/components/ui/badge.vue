<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'default'
    }
})

const badgeClasses = computed(() => {
    const baseClasses = 'cursor-default inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden'

    const variants: { [key: string]: string } = {
        default: 'border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90',
        secondary: 'border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90',
        success: 'border-transparent bg-green-500 text-white [a&]:hover:bg-green-500/90 focus-visible:ring-green-500/20 dark:focus-visible:ring-green-500/40 dark:bg-green-500/60',
        destructive: 'border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60',
        outline: 'text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground'
    }

    return [
        baseClasses,
        variants[props.variant] || variants.default
    ]
})
</script>

<template>
    <span :class="badgeClasses">
        <slot></slot>
    </span>
</template>
