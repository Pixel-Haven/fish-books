<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import DefaultLayout from "@/layouts/Default.vue"

defineProps<{
    title: string
}>()

defineOptions({
    layout: (h: any, page: any) => h(DefaultLayout, { hideHeader: true, class: "flex flex-col justify-center items-center" }, () => page)
})

const goBack = () => {
    window.history.back()
}
</script>

<template>
    <Head :title="title" />
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="text-center space-y-8 staggered-float-in" style="--anim-delay: 1">
            <!-- Title with slide-up animation -->
            <div class="space-y-4 delay-200">
                <h1 class="text-2xl md:text-4xl font-bold text-grey-800 dark:text-grey-200">{{ title }}</h1>

                <!-- Description with delayed slide-up -->
                <p class="text-lg md:text-xl text-grey-600 dark:text-grey-400 max-w-md mx-auto leading-relaxed delay-300">
                    Coming Soon...
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
