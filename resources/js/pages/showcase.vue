<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/layouts/Default.vue'
import ProjectCards from '@/components/project-cards.vue'
import ProjectFilters from '@/components/project-filters.vue'
import ProjectTable from '@/components/project-table.vue'
import StatsCards from '@/components/stats-cards.vue'
import UsersTable from '@/components/users-table.vue'
import Button from '@/components/ui/button.vue'
import Card from '@/components/ui/card.vue'
import Separator from '@/components/ui/separator.vue'

const page = usePage<{ appName?: string }>()
const appName = computed(() => page.props.appName ?? 'Laravel')

const searchTerm = ref('')

const showcaseProjects = [
    {
        projectCode: 'PX-2025-001',
        lead: {
            name: 'Aishath Ahmed',
            id: 'PX-LEAD-01',
        },
        stream: {
            name: 'Experience',
            summary: 'Mobile app redesign',
        },
        budget: 'USD 650K',
        burn: 'USD 210K',
        kickoff: '2024-07-14',
        targetLaunch: '2025-07-14',
        status: 'in-progress' as const,
    },
    {
        projectCode: 'PX-2025-002',
        lead: {
            name: 'Mohamed Razeen',
            id: 'PX-LEAD-02',
        },
        stream: {
            name: 'Data',
            summary: 'Realtime analytics pipeline',
        },
        budget: 'USD 420K',
        burn: 'USD 420K',
        kickoff: '2023-11-01',
        targetLaunch: '2024-11-01',
        status: 'completed' as const,
    },
    {
        projectCode: 'PX-2025-003',
        lead: {
            name: 'Ibrahim Munaz',
            id: 'PX-LEAD-03',
        },
        stream: {
            name: 'Platform',
            summary: 'API gateway hardening',
        },
        budget: 'USD 315K',
        burn: 'USD 110K',
        kickoff: '2024-01-20',
        targetLaunch: '2025-01-20',
        status: 'at-risk' as const,
    },
]

const showcaseUsers = {
    data: [
        { rcNumber: 'A123456', name: 'Aishath Ahmed', phoneNumber: '+960 777-1234' },
        { rcNumber: 'A579923', name: 'Mohamed Razeen', phoneNumber: '+960 973-8822' },
        { rcNumber: 'A332114', name: 'Ibrahim Munaz', phoneNumber: '+960 735-1100' },
    ],
}

const filteredProjects = computed(() => {
    if (!searchTerm.value.trim()) {
        return showcaseProjects
    }

    const term = searchTerm.value.toLowerCase()
    return showcaseProjects.filter((project) => {
        return [
            project.projectCode,
            project.lead.name,
            project.lead.id,
            project.stream.name,
            project.stream.summary,
            project.status,
        ]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(term))
    })
})

const handleSearchChange = (value: string) => {
    searchTerm.value = value
}

defineOptions({
    layout: DefaultLayout,
})
</script>

<template>
    <div class="space-y-16">
        <section class="staggered-float-in space-y-6 text-center" style="--anim-delay: 0">
            <p
                class="mx-auto w-fit rounded-full bg-teal-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-teal-600">
                {{ appName }}
            </p>
            <h1 class="text-4xl font-bold tracking-tight text-grey-900 dark:text-white sm:text-5xl">
                Launch new product ideas faster.
            </h1>
            <p class="mx-auto max-w-2xl text-base text-grey-700 dark:text-grey-300">
                A lean Laravel + Inertia + Vue template with Tailwind styling baked-in. Use this showcase page as a
                visual
                reference for the component library before you start building features.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <Button class="glass-card bg-teal-500/90 px-6 py-3 text-base font-semibold text-white">
                    Explore Components
                </Button>
                <Button variant="ghost" class="glass-card px-6 py-3 text-base text-grey-700 dark:text-grey-200">
                    View Docs
                </Button>
            </div>
        </section>

        <section class="space-y-6">
            <header class="space-y-2">
                <h2 class="text-2xl font-semibold text-grey-900 dark:text-white">KPIs at a glance</h2>
                <p class="text-sm text-grey-600 dark:text-grey-300">
                    Ready-made stat tiles to highlight the numbers that matter.
                </p>
            </header>
            <StatsCards />
        </section>

        <Separator class="my-12 opacity-60" />

        <section class="space-y-6">
            <header class="space-y-2">
                <h2 class="text-2xl font-semibold text-grey-900 dark:text-white">Featured products</h2>
                <p class="text-sm text-grey-600 dark:text-grey-300">
                    Glassmorphic cards with subtle animation for premium offerings.
                </p>
            </header>
            <ProjectCards />
        </section>

        <section class="space-y-6">
            <header class="space-y-2">
                <h2 class="text-2xl font-semibold text-grey-900 dark:text-white">Initiatives overview</h2>
                <p class="text-sm text-grey-600 dark:text-grey-300">
                    Combine filters, tables, and badges for interactive back-office views.
                </p>
            </header>
            <ProjectFilters @search-change="handleSearchChange" />
            <ProjectTable :data="filteredProjects" />
        </section>

        <section class="space-y-6">
            <header class="space-y-2">
                <h2 class="text-2xl font-semibold text-grey-900 dark:text-white">Customer snapshots</h2>
                <p class="text-sm text-grey-600 dark:text-grey-300">
                    Use the base table component to highlight key contact information.
                </p>
            </header>
            <UsersTable :data="showcaseUsers" />
        </section>

        <section>
            <Card class="glass-card flex flex-col gap-4 rounded-2xl p-8 text-center">
                <h3 class="text-xl font-semibold text-grey-900 dark:text-white">Ready for production hardening</h3>
                <p class="text-sm text-grey-600 dark:text-grey-300">
                    Start by tailoring this page, then wire your own features, routes, and data sources. Everything
                    unnecessary has been stripped away so you can focus on building.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <Button class="glass-card bg-teal-500/90 px-5 py-2 text-base font-semibold text-white">
                        Scaffold Authentication
                    </Button>
                    <Button variant="outline" class="glass-card border-teal-400 px-5 py-2 text-base text-teal-600">
                        Add API Endpoints
                    </Button>
                </div>
            </Card>
        </section>
    </div>
</template>
