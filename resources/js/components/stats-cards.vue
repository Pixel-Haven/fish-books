<script setup lang="ts">
const stats = [
    { label: "Active Initiatives", value: "6", unit: "", color: "text-teal-600" },
    { label: "Average Sprint Velocity", value: "28", unit: "Days", color: "text-teal-600" },
    { label: "Total Pipeline Value", value: "USD 1.3M", unit: "", color: "text-teal-600" },
    { label: "Launches This Quarter", value: "4", unit: "", color: "text-teal-600" },
    { label: "Items At Risk", value: "2", unit: "", color: "text-teal-600" },
    { label: "Experiments Running", value: "9", unit: "", color: "text-teal-600" },
]
</script>

<template>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div v-for="(stat, index) in stats" :key="index" class="glass-card rounded-xl p-4 staggered-float-in"
            :style="{ '--anim-delay': index }">
            <div class="text-xs text-grey-700 dark:text-grey-300 mb-1">{{ stat.label }}</div>
            <div class="flex items-baseline space-x-1">
                <span :class="['text-2xl font-bold', stat.color]">{{ stat.value }}</span>
                <span v-if="stat.unit" class="text-sm text-grey-700 dark:text-grey-300">{{ stat.unit }}</span>
            </div>
        </div>
    </div>
</template>
