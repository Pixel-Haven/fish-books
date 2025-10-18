<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import DefaultLayout from "@/layouts/Default.vue"

const props = defineProps<{
    status: number
}>()

defineOptions({
    layout: (h: any, page: any) => h(DefaultLayout, { hideHeader: true, class: "flex flex-col justify-center items-center" }, () => page)
})

const title = computed(() => {
    return {
        503: "Service Unavailable",
        500: "Server Error",
        404: "Page Not Found",
        403: "Forbidden",
    }[props.status]
})

const description = computed(() => {
    return {
        503: "Sorry, we are doing some maintenance. Please check back soon.",
        500: "Whoops, something went wrong on our servers.",
        404: "Sorry, the page you are looking for could not be found.",
        403: "Sorry, you are forbidden from accessing this page.",
    }[props.status]
})

const statusColor = computed(() => {
    return {
        503: "text-yellow-300",
        500: "text-red-300",
        404: "text-blue-300",
        403: "text-orange-300",
    }[props.status] || "text-grey-300"
})

const goBack = () => {
    window.history.back()
}
</script>

<template>
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="text-center space-y-8 staggered-float-in" style="--anim-delay: 1">
            <!-- Large animated status code -->
            <div class="relative">
                <div
                    :class="['text-9xl md:text-[12rem] font-black animate-bounce duration-1000 select-none', statusColor]">
                    {{ status }}
                </div>
                <div
                    class="absolute inset-0 text-9xl md:text-[12rem] font-black text-grey-200 dark:text-grey-800 animate-pulse -z-10 blur-sm">
                    {{ status }}
                </div>
            </div>

            <!-- Title with slide-up animation -->
            <div class="space-y-4 delay-200">
                <h1 class="text-2xl md:text-4xl font-bold text-grey-800 dark:text-grey-200">{{ title }}</h1>

                <!-- Description with delayed slide-up -->
                <p class="text-lg md:text-xl text-grey-600 dark:text-grey-400 max-w-md mx-auto leading-relaxed delay-300">
                    {{ description }}
                </p>
            </div>

            <!-- Action buttons with stagger animation -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center delay-500">
                <button @click="goBack"
                    class="cursor-pointer px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all duration-300 hover:shadow-lg">
                    Go Back
                </button>
                <Link href="/"
                    class="cursor-pointer px-6 py-3 border-2 border-grey-300 dark:border-grey-600 text-grey-700 font-semibold rounded-lg transition-all duration-300">
                Home Page
                </Link>
            </div>
        </div>

        <!-- Floating background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                class="absolute top-1/4 left-1/4 w-32 h-32 bg-blue-200 dark:bg-blue-800/10 rounded-full opacity-20 animate-ping">
            </div>
            <div
                class="absolute top-3/4 right-1/4 w-24 h-24 bg-purple-200 dark:bg-purple-800/10 rounded-full opacity-20 animate-pulse delay-1000">
            </div>
            <div
                class="absolute bottom-1/4 left-1/3 w-20 h-20 bg-pink-200 dark:bg-pink-800/10 rounded-full opacity-20 animate-ping delay-700">
            </div>
        </div>
    </div>
</template>
