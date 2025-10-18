<template>
    <div class="relative">
        <!-- Input Field -->
        <div class="relative">
            <input ref="inputRef" type="text" :value="displayValue" @click="togglePicker" @keydown="handleKeydown"
                :placeholder="placeholder" :disabled="disabled"
                :class="['file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm', 'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]', 'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive', 'bg-white/50 dark:bg-grey-900 focus:bg-white/70 dark:focus:bg-grey-900/70 text-grey-800 dark:text-grey-200', $attrs.class]"
                readonly />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <CalendarDaysIcon class="h-5 w-5 text-grey-400 dark:text-grey-500" />
            </div>
        </div>

        <!-- Datepicker Dropdown -->
        <Transition :enter-active-class="transitionClasses.enterActive" :enter-from-class="transitionClasses.enterFrom"
            :enter-to-class="transitionClasses.enterTo" :leave-active-class="transitionClasses.leaveActive"
            :leave-from-class="transitionClasses.leaveFrom" :leave-to-class="transitionClasses.leaveTo">
            <div v-if="isOpen" ref="pickerRef" :class="dropdownClasses" @click.stop>

                <!-- Calendar View -->
                <div v-if="currentView === 'calendar'">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-4 py-3">
                        <button @click="previousMonth" type="button"
                            class="p-1 rounded-md hover:bg-grey-100 dark:hover:bg-grey-800 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 transition-colors">
                            <ChevronLeftIcon class="h-5 w-5 text-grey-600 dark:text-grey-400" />
                        </button>

                        <div class="flex space-x-2">
                            <button @click="currentView = 'month'" type="button"
                                class="text-sm font-semibold text-grey-900 dark:text-grey-100 hover:bg-grey-100 dark:hover:bg-grey-800 px-2 py-1 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 transition-colors">
                                {{ monthNames[currentMonth] }}
                            </button>

                            <button @click="currentView = 'year'" type="button"
                                class="text-sm font-semibold text-grey-900 dark:text-grey-100 hover:bg-grey-100 dark:hover:bg-grey-800 px-2 py-1 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 transition-colors">
                                {{ currentYear }}
                            </button>
                        </div>

                        <button @click="nextMonth" type="button"
                            class="p-1 rounded-md hover:bg-grey-100 dark:hover:bg-grey-800 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 transition-colors">
                            <ChevronRightIcon class="h-5 w-5 text-grey-600 dark:text-grey-400" />
                        </button>
                    </div>

                    <!-- Days of Week -->
                    <div class="grid grid-cols-7 gap-0 border-b border-grey-200 dark:border-grey-700">
                        <div v-for="day in dayNames" :key="day"
                            class="px-1 py-1 text-xs font-medium text-grey-500 dark:text-grey-400 text-center">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-0 my-1">
                        <button v-for="day in calendarDays" :key="`${day.date}-${day.isCurrentMonth}`"
                            @click="selectDate(day)" type="button" :disabled="isDateDisabled(day)"
                            :class="getDayClasses(day)">
                            {{ day.day }}
                        </button>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-t border-grey-200 dark:border-grey-700">
                        <button @click="selectToday" type="button"
                            class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 rounded px-2 py-1 transition-colors">
                            Today
                        </button>

                        <button @click="clearDate" type="button"
                            class="text-sm text-grey-600 dark:text-grey-400 hover:text-grey-800 dark:hover:text-grey-200 focus:outline-none focus:ring-2 focus:ring-grey-500 dark:focus:ring-grey-400 rounded px-2 py-1 transition-colors">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Month Selection View -->
                <div v-else-if="currentView === 'month'" class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-grey-900 dark:text-grey-100">Select Month</h3>
                        <button @click="currentView = 'calendar'" type="button"
                            class="text-grey-500 dark:text-grey-400 hover:text-grey-700 dark:hover:text-grey-200 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 rounded-md p-1">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2 max-h-72 overflow-y-auto">
                        <button v-for="(month, index) in monthNames" :key="index" @click="selectMonth(index)"
                            @keydown="handleGridKeydown($event, 'month', index)" type="button"
                            :class="getMonthClasses(index)">
                            {{ month }}
                        </button>
                    </div>
                </div>

                <!-- Year Selection View -->
                <div v-else-if="currentView === 'year'" class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-grey-900 dark:text-grey-100">Select Year</h3>
                        <button @click="currentView = 'calendar'" type="button"
                            class="text-grey-500 dark:text-grey-400 hover:text-grey-700 dark:hover:text-grey-200 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 rounded-md p-1">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-4 gap-2 max-h-64 overflow-y-auto" ref="yearGridRef">
                        <button v-for="year in yearRange" :key="year" @click="selectYear(year)"
                            @keydown="handleGridKeydown($event, 'year', year)" type="button"
                            :class="getYearClasses(year)">
                            {{ year }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import dayjs from 'dayjs'
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

interface CalendarDay {
    date: string
    day: number
    isCurrentMonth: boolean
    isToday: boolean
    isSelected: boolean
    isDisabled: boolean
}

interface Props {
    modelValue?: string | Date | null
    format?: string
    placeholder?: string
    disabled?: boolean
    minDate?: string | Date
    maxDate?: string | Date
    locale?: string
    timezone?: string
    class?: string | any
}

interface Emits {
    (e: 'update:modelValue', value: string | null): void
    (e: 'change', value: string | null): void
}

const props = withDefaults(defineProps<Props>(), {
    format: 'YYYY-MM-DD',
    placeholder: 'Select date',
    disabled: false,
    locale: 'en',
    class: '',
    timezone: dayjs.tz.guess()
})

const emit = defineEmits<Emits>()

// Refs
const inputRef = ref<HTMLInputElement>()
const pickerRef = ref<HTMLDivElement>()
const yearGridRef = ref<HTMLDivElement>()
const isOpen = ref(false)
const currentMonth = ref(dayjs().month())
const currentYear = ref(dayjs().year())
const pickerPosition = ref<'bottom' | 'top'>('bottom')
const currentView = ref<'calendar' | 'month' | 'year'>('calendar')

// Computed properties
const selectedDate = computed(() => {
    if (!props.modelValue) return null
    return dayjs(props.modelValue).tz(props.timezone)
})

const displayValue = computed(() => {
    if (!selectedDate.value) return ''
    return selectedDate.value.format(props.format)
})

const currentDate = computed(() => {
    return dayjs().year(currentYear.value).month(currentMonth.value).tz(props.timezone)
})

const monthNames = computed(() => {
    return Array.from({ length: 12 }, (_, i) =>
        dayjs().month(i).format('MMMM')
    )
})

const dayNames = computed(() => {
    return Array.from({ length: 7 }, (_, i) =>
        dayjs().day(i).format('dd')
    )
})

const yearRange = computed(() => {
    const baseYear = selectedDate.value ? selectedDate.value.year() : dayjs().year()
    const years = []
    for (let year = baseYear - 50; year <= baseYear + 50; year++) {
        years.push(year)
    }
    return years
})

const calendarDays = computed(() => {
    const firstDay = currentDate.value.startOf('month')
    const lastDay = currentDate.value.endOf('month')
    const startDate = firstDay.startOf('week')
    const endDate = lastDay.endOf('week')

    const days: CalendarDay[] = []
    let current = startDate

    while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
        const isCurrentMonth = current.month() === currentMonth.value
        const isToday = current.isSame(dayjs().tz(props.timezone), 'day')
        const isSelected = selectedDate.value ? current.isSame(selectedDate.value, 'day') : false
        const isDisabled = isDateDisabled({
            date: current.format('YYYY-MM-DD'),
            day: current.date(),
            isCurrentMonth,
            isToday,
            isSelected,
            isDisabled: false
        })

        days.push({
            date: current.format('YYYY-MM-DD'),
            day: current.date(),
            isCurrentMonth,
            isToday,
            isSelected,
            isDisabled
        })

        current = current.add(1, 'day')
    }

    return days
})

const dropdownClasses = computed(() => {
    const baseClasses = [
        'datepicker-dropdown',
        'absolute z-[9999] bg-white dark:bg-grey-900 rounded-lg',
        'shadow-2xl',
        'min-w-[28px] md:min-w-[250px] max-w-sm',
        'transform transition-all duration-200 ease-out'
    ]

    if (pickerPosition.value === 'bottom') {
        baseClasses.push('mt-1 top-full')
    } else {
        baseClasses.push('mb-1 bottom-full')
    }

    return baseClasses.join(' ')
})

const transitionClasses = computed(() => {
    const baseTransition = {
        enterActive: 'transition duration-200 ease-out',
        leaveActive: 'transition duration-75 ease-in',
        enterTo: 'transform scale-100 opacity-100',
        leaveFrom: 'transform scale-100 opacity-100'
    }

    if (pickerPosition.value === 'bottom') {
        return {
            ...baseTransition,
            enterFrom: 'transform scale-95 opacity-0 -translate-y-2',
            leaveTo: 'transform scale-95 opacity-0 -translate-y-2'
        }
    } else {
        return {
            ...baseTransition,
            enterFrom: 'transform scale-95 opacity-0 translate-y-2',
            leaveTo: 'transform scale-95 opacity-0 translate-y-2'
        }
    }
})

// Methods
const calculatePosition = async () => {
    if (!inputRef.value) return

    await nextTick()

    const inputRect = inputRef.value.getBoundingClientRect()
    const viewportHeight = window.innerHeight
    const pickerHeight = 350 // Approximate height of the picker with some buffer
    const buffer = 20 // Additional buffer from viewport edges

    const spaceBelow = viewportHeight - inputRect.bottom - buffer
    const spaceAbove = inputRect.top - buffer

    // If there's not enough space below and there's more space above, show above
    if (spaceBelow < pickerHeight && spaceAbove > spaceBelow && spaceAbove >= pickerHeight) {
        pickerPosition.value = 'top'
    } else {
        pickerPosition.value = 'bottom'
    }
}

const togglePicker = async () => {
    if (props.disabled) return
    isOpen.value = !isOpen.value

    if (isOpen.value) {
        currentView.value = 'calendar'
        await calculatePosition()

        if (selectedDate.value) {
            currentMonth.value = selectedDate.value.month()
            currentYear.value = selectedDate.value.year()
        }
    }
}

const closePicker = () => {
    isOpen.value = false
    currentView.value = 'calendar'
}

const selectMonth = (monthIndex: number) => {
    currentMonth.value = monthIndex
    currentView.value = 'calendar'
}

const selectYear = (year: number) => {
    currentYear.value = year
    currentView.value = 'calendar'
}

const getMonthClasses = (monthIndex: number): string => {
    const classes = [
        'w-full px-2 py-2 text-xs rounded-md transition-all duration-200',
        'focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400',
    ]

    if (monthIndex === currentMonth.value) {
        classes.push('bg-teal-600 dark:bg-teal-500 text-white shadow-md')
    } else {
        classes.push('text-grey-900 dark:text-grey-100 hover:bg-grey-100 dark:hover:bg-grey-800 hover:shadow-sm')
    }

    return classes.join(' ')
}

const getYearClasses = (year: number): string => {
    const classes = [
        'w-full px-3 py-2 text-sm rounded-md transition-all duration-200',
        'focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400',
        'transform hover:scale-105 active:scale-95'
    ]

    if (year === currentYear.value) {
        classes.push('bg-teal-600 dark:bg-teal-500 text-white shadow-md')
    } else {
        classes.push('text-grey-900 dark:text-grey-100 hover:bg-grey-100 dark:hover:bg-grey-800 hover:shadow-sm')
    }

    return classes.join(' ')
}

const selectDate = (day: CalendarDay) => {
    if (day.isDisabled) return

    const newDate = dayjs(day.date).tz(props.timezone)
    const formattedDate = newDate.format(props.format)

    emit('update:modelValue', formattedDate)
    emit('change', formattedDate)
    closePicker()
}

const selectToday = () => {
    const today = dayjs().tz(props.timezone)
    const formattedDate = today.format(props.format)

    emit('update:modelValue', formattedDate)
    emit('change', formattedDate)
    closePicker()
}

const clearDate = () => {
    emit('update:modelValue', null)
    emit('change', null)
    closePicker()
}

const previousMonth = () => {
    const newDate = currentDate.value.subtract(1, 'month')
    currentMonth.value = newDate.month()
    currentYear.value = newDate.year()
}

const nextMonth = () => {
    const newDate = currentDate.value.add(1, 'month')
    currentMonth.value = newDate.month()
    currentYear.value = newDate.year()
}

const updateCalendar = () => {
    // Calendar will be reactive to currentMonth and currentYear changes
}

const isDateDisabled = (day: CalendarDay): boolean => {
    const date = dayjs(day.date).tz(props.timezone)

    if (props.minDate && date.isBefore(dayjs(props.minDate).tz(props.timezone), 'day')) {
        return true
    }

    if (props.maxDate && date.isAfter(dayjs(props.maxDate).tz(props.timezone), 'day')) {
        return true
    }

    return false
}

const getDayClasses = (day: CalendarDay): string => {
    const classes = [
        'relative mx-1 px-1 py-1 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-teal-400 focus:z-10 transition-colors'
    ]

    if (!day.isCurrentMonth) {
        classes.push('text-grey-300 dark:text-grey-600')
    } else if (day.isDisabled) {
        classes.push('text-grey-300 dark:text-grey-600 cursor-not-allowed')
    } else {
        classes.push('text-grey-900 dark:text-grey-100 hover:bg-grey-100 dark:hover:bg-grey-800 cursor-pointer')
    }

    if (day.isSelected) {
        classes.push('bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600')
    }

    if (day.isToday && !day.isSelected) {
        classes.push('font-semibold text-teal-600 dark:text-teal-400')
    }

    return classes.join(' ')
}

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        togglePicker()
    } else if (event.key === 'Escape') {
        closePicker()
    }
}

const handleGridKeydown = (event: KeyboardEvent, type: 'month' | 'year', value: number) => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        if (type === 'month') {
            selectMonth(value)
        } else {
            selectYear(value)
        }
    } else if (event.key === 'Escape') {
        currentView.value = 'calendar'
    }
}

const handleClickOutside = (event: Event) => {
    if (
        pickerRef.value &&
        inputRef.value &&
        !pickerRef.value.contains(event.target as Node) &&
        !inputRef.value.contains(event.target as Node)
    ) {
        closePicker()
    }
}

const handleResize = () => {
    if (isOpen.value) {
        calculatePosition()
    }
}

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleResize)
    window.addEventListener('scroll', handleResize, true)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('scroll', handleResize, true)
})

// Watch for changes in modelValue to update the calendar view
watch(() => props.modelValue, (newValue) => {
    if (newValue && dayjs(newValue).isValid()) {
        const date = dayjs(newValue).tz(props.timezone)
        currentMonth.value = date.month()
        currentYear.value = date.year()
    }
})

// Watch for picker open state to recalculate position
watch(isOpen, (newValue) => {
    if (newValue) {
        calculatePosition()
    }
})

// Watch for view changes to scroll to current year/month
watch(currentView, async (newView) => {
    if (newView === 'year') {
        await nextTick()
        scrollToCurrentYear()
    }
})

const scrollToCurrentYear = () => {
    if (yearGridRef.value) {
        const baseYear = selectedDate.value ? selectedDate.value.year() : dayjs().year()
        const yearIndex = currentYear.value - (baseYear - 50)
        const currentYearButton = yearGridRef.value.querySelector(`button:nth-child(${yearIndex + 1})`)
        if (currentYearButton) {
            currentYearButton.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
    }
}
</script>

<style scoped>
/* Ensure the datepicker container has proper stacking context */
.relative {
    isolation: isolate;
}

/* Custom scrollbar for year/month grids */
.max-h-64 {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.max-h-64::-webkit-scrollbar {
    width: 6px;
}

.max-h-64::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

.max-h-64::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.max-h-64::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Dark mode scrollbar */
.dark .max-h-64 {
    scrollbar-color: #4b5563 #374151;
}

.dark .max-h-64::-webkit-scrollbar-track {
    background: #374151;
}

.dark .max-h-64::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .max-h-64::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Additional styles for better positioning on small screens */
@media (max-width: 320px) {
    .datepicker-dropdown {
        min-width: calc(100vw - 2rem);
        max-width: calc(100vw - 2rem);
    }
}

/* Smooth scroll behavior */
.overflow-y-auto {
    scroll-behavior: smooth;
}
</style>
