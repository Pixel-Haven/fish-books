<script setup lang="ts">
import Button from "@/components/ui/button.vue"
import Input from "@/components/ui/input.vue"
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline"

const emit = defineEmits(['search-change'])

const onSearchChange = (event: Event) => {
    emit('search-change', (event.target as HTMLInputElement).value)
}
</script>

<template>
    <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row items-stretch sm:items-center justify-between staggered-float-in"
        style="--anim-delay: 2">
        <div class="flex-1 flex flex-row justify-stretch sm:justify-start items-center space-x-2">
            <div class="sm:flex-shrink">
                <Button variant="secondary"
                    class="glass-card w-full bg-teal-500/80 dark:text-white text-grey-800 border-teal-400 hover:bg-teal-600/80">
                    All
                </Button>
            </div>
            <div class="sm:flex-shrink">
                <Button variant="ghost" class="glass-card w-full hover:bg-white/50 dark:hover:text-white text-grey-500">
                    In Progress
                </Button>
            </div>
            <div class="sm:flex-shrink">
                <Button variant="ghost" class="glass-card w-full hover:bg-white/50 dark:hover:text-white text-grey-500">
                    Completed
                </Button>
            </div>
        </div>
        <div class="relative">
            <Input @input="onSearchChange" placeholder="Search initiatives..."
                class="glass-card pl-10 w-full sm:w-80 bg-white/30 border-white/40 text-grey-800 placeholder:text-grey-600 dark:placeholder:text-grey-400 focus:bg-white/50" />
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 text-teal-500 w-4 h-4" />
        </div>
    </div>
</template>
