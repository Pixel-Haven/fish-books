<script setup lang="ts">
import Table from "@/components/ui/table.vue"

type ShowcaseUser = {
    rcNumber: string
    name: string
    phoneNumber: string
}

defineProps<{
    data?: { data: ShowcaseUser[] }
}>()
</script>

<template>
    <div class="glass-card rounded-2xl p-6 staggered-float-in" style="--anim-delay: 4.5">
        <Table>
            <thead class="[&_tr]:border-b">
                <tr class="border-grey-200 dark:border-grey-800 hover:bg-transparent">
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        RC Number
                    </th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Name
                    </th>
                    <th
                        class="h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-teal-600 dark:text-teal-300">
                        Phone Number
                    </th>
                </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
                <template v-if="data && data.data && data.data.length > 0">
                    <tr v-for="(user, index) in data.data" :key="index"
                        class="border-grey-200 dark:border-grey-800 hover:bg-white/5">
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ user.rcNumber }}
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ user.name }}
                        </td>
                        <td class="p-2 align-middle whitespace-nowrap text-grey-800 dark:text-grey-200">
                            {{ user.phoneNumber }}
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <tr>
                        <td colspan="3" class="text-center text-grey-600 dark:text-grey-400 py-4">
                            No users found.
                        </td>
                    </tr>
                </template>
            </tbody>
        </Table>
    </div>
</template>
