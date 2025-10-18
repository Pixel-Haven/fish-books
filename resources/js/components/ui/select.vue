<template>
    <div class="relative">
        <select
            :value="modelValue"
            @change="handleChange"
            :disabled="disabled"
            :class="[
                'w-full h-9 px-3 py-1 text-sm rounded-md border bg-transparent shadow-xs transition-[color,box-shadow] outline-none',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                'bg-white/50 dark:bg-grey-900 focus:bg-white/70 dark:focus:bg-grey-900/70',
                'text-grey-800 dark:text-grey-200 border-grey-300 dark:border-grey-700',
                'disabled:opacity-50 disabled:cursor-not-allowed',
                $attrs.class
            ]"
        >
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="getOptionValue(option)"
                :value="getOptionValue(option)"
            >
                {{ getOptionLabel(option) }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
interface Option {
    value: any
    label: string
    [key: string]: any
}

interface Props {
    modelValue?: any
    options: Option[] | any[]
    placeholder?: string
    disabled?: boolean
    valueKey?: string
    labelKey?: string
}

interface Emits {
    (e: 'update:modelValue', value: any): void
}

const props = withDefaults(defineProps<Props>(), {
    valueKey: 'value',
    labelKey: 'label',
    disabled: false
})

const emit = defineEmits<Emits>()

const getOptionValue = (option: any) => {
    if (typeof option === 'object') {
        return option[props.valueKey]
    }
    return option
}

const getOptionLabel = (option: any) => {
    if (typeof option === 'object') {
        return option[props.labelKey]
    }
    return option
}

const handleChange = (event: Event) => {
    const target = event.target as HTMLSelectElement
    emit('update:modelValue', target.value)
}
</script>
