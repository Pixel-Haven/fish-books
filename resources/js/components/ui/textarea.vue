<template>
    <textarea
        :value="modelValue"
        @input="handleInput"
        :placeholder="placeholder"
        :disabled="disabled"
        :rows="rows"
        :class="[
            'w-full px-3 py-2 text-sm rounded-md border bg-transparent shadow-xs transition-[color,box-shadow] outline-none resize-none',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            'bg-white/50 dark:bg-grey-900 focus:bg-white/70 dark:focus:bg-grey-900/70',
            'text-grey-800 dark:text-grey-200 border-grey-300 dark:border-grey-700',
            'placeholder:text-grey-500 dark:placeholder:text-grey-400',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            $attrs.class
        ]"
    ></textarea>
</template>

<script setup lang="ts">
interface Props {
    modelValue?: string
    placeholder?: string
    disabled?: boolean
    rows?: number
}

interface Emits {
    (e: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
    disabled: false
})

const emit = defineEmits<Emits>()

const handleInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement
    emit('update:modelValue', target.value)
}
</script>
