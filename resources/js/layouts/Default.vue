<template>
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-gradient-radial from-transparent via-blue-100/30 to-teal-100/40 dark:hidden" />
        <div
            class="absolute top-1/4 left-1/4 w-96 h-96 bg-teal-200/20 rounded-full blur-3xl animate-pulse dark:hidden" />
        <div
            class="absolute bottom-1/3 right-1/3 w-80 h-80 bg-purple-200/20 rounded-full blur-3xl animate-pulse delay-1000 dark:hidden" />
        <div
            class="absolute top-1/2 right-1/4 w-72 h-72 bg-pink-200/15 rounded-full blur-3xl animate-pulse delay-500 dark:hidden" />
        <div
            class="absolute bottom-1/4 left-1/2 w-64 h-64 bg-indigo-200/15 rounded-full blur-3xl animate-pulse delay-1500 dark:hidden" />

        <main class="relative z-10 mx-auto max-w-6xl px-4 py-12 sm:px-8 lg:px-12">
            <slot />
        </main>
    </div>
</template>
