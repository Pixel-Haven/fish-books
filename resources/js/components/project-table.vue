<script setup lang="ts">
import Table from "@/components/ui/table.vue"
import Badge from "./ui/badge.vue"
import { formatDate } from '@/utils/functions';

type ShowcaseProject = {
    projectCode: string
    lead: {
        name: string
        id: string
    }
    stream: {
        name: string
        summary: string
    }
    budget: string
    burn: string
    kickoff: string
    targetLaunch: string
    status: "in-progress" | "completed" | "at-risk" | "blocked" | "planning"
}

defineProps<{
    data?: ShowcaseProject[]
}>()

// For uppercase display in this component
const formatDateUpper = (dateString: string) => {
    return formatDate(dateString, 'DD MMM YYYY').toUpperCase();
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'in-progress':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'at-risk':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'blocked':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <div class="glass-card rounded-2xl p-6 staggered-float-in"
        style="--anim-delay: 4; --anim-jump: 0.5%; --anim-limit: -4%;">
        <Table>
            <thead class="[&_tr]:border-b">
                <tr class="border-grey-200 dark:border-grey-800 hover:bg-transparent">
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Project Code</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Initiative Lead</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Stream</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Budget</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Kickoff</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Target Launch</th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Status</th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                <template v-if="data && data && data.length > 0">
                    <tr v-for="(project, index) in data" :key="index"
                        class="border-grey-200 dark:border-grey-800 hover:bg-white/5">
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ project.projectCode }}
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            <div>
                                <div class="font-medium">{{ project.lead.name }}</div>
                                <div class="text-sm text-grey-600 dark:text-grey-400">{{ project.lead.id }}</div>
                            </div>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            <div>
                                <div class="font-medium">{{ project.stream.name }}</div>
                                <div class="text-sm text-grey-600 dark:text-grey-400">
                                    {{ project.stream.summary }}
                                </div>
                            </div>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            <div class="font-medium">{{ project.budget }}</div>
                            <div class="text-sm text-grey-600 dark:text-grey-400">
                                Burn: {{ project.burn }}
                            </div>
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ formatDate(project.kickoff) }}
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ formatDate(project.targetLaunch) }}
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            <Badge :class="getStatusColor(project.status)">
                                {{ project.status.replace('-', ' ') }}
                            </Badge>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <tr>
                        <td colspan="7" class="text-center text-grey-600 dark:text-grey-400 py-4">
                            No initiatives available yet.
                        </td>
                    </tr>
                </template>
            </tbody>
        </Table>
    </div>
</template>
