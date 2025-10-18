<script setup lang="ts">
import Card from "@/components/ui/card.vue"

const projects = [
    {
        launchInDays: 12,
        name: "Pixel Drive",
        focus: "Mobile Experience",
        budget: "USD 650K",
        timeframe: "Jan 2025 – Jan 2026",
        image: "https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=800&q=60",
    },
    {
        launchInDays: 34,
        name: "Pixel Atlas",
        focus: "Data Platform",
        budget: "USD 420K",
        timeframe: "Mar 2025 – Mar 2026",
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=60",
    },
    {
        launchInDays: 3,
        name: "Pixel Pulse",
        focus: "Analytics Suite",
        budget: "USD 115K",
        timeframe: "Oct 2024 – Oct 2025",
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=60",
    },
]
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="(project, index) in projects" :key="index"
            class="glass-card relative overflow-hidden rounded-2xl p-6 staggered-float-in"
            :style="{ '--anim-delay': index }">
            <div class="absolute inset-0 opacity-10">
                <img :src="project.image" :alt="project.name" class="h-full w-full object-cover" />
            </div>
            <div class="relative z-10 flex h-full flex-col">
                <div class="mb-4">
                    <div class="text-sm text-grey-700 dark:text-grey-300">Launch in</div>
                    <div class="text-2xl font-bold text-teal-900 dark:text-teal-100">{{ project.launchInDays }} days
                    </div>
                </div>
                <div class="mb-6">
                    <div class="text-sm text-grey-700 dark:text-grey-300">Initiative</div>
                    <div class="text-lg font-semibold text-teal-900 dark:text-teal-100">{{ project.name }}</div>
                    <div class="text-sm text-teal-800 dark:text-teal-200">{{ project.focus }}</div>
                </div>
                <div class="mt-auto flex items-end justify-between">
                    <div>
                        <div class="text-sm text-grey-700 dark:text-grey-300">Budget</div>
                        <div class="text-xl font-bold text-teal-900 dark:text-teal-100">{{ project.budget }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-grey-700 dark:text-grey-300">Timeline</div>
                        <div class="text-sm text-teal-600 dark:text-teal-200">{{ project.timeframe }}</div>
                    </div>
                </div>
            </div>
        </Card>
    </div>
</template>
