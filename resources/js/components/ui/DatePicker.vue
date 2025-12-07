<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';

const props = defineProps<{
    modelValue?: string | null;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    inputClass?: string;
    minDate?: string;
    maxDate?: string;
}>();

const emit = defineEmits(['update:modelValue', 'clear']);

const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);

const currentViewDate = ref(new Date());

const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

const weekDays = ['DO', 'LU', 'MA', 'MI', 'JU', 'VI', 'SA'];

const initializeViewDate = () => {
    if (props.modelValue) {
        const [year, month, day] = props.modelValue.split('-').map(Number);
        currentViewDate.value = new Date(year, month - 1, day);
    } else {
        currentViewDate.value = new Date();
    }
};

watch(() => props.modelValue, initializeViewDate, { immediate: true });

const currentMonthName = computed(() => monthNames[currentViewDate.value.getMonth()]);
const currentYear = computed(() => currentViewDate.value.getFullYear());

const formattedDisplayDate = computed(() => {
    if (!props.modelValue) return '';
    const [year, month, day] = props.modelValue.split('-').map(Number);
    return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
});

const calendarDays = computed(() => {
    const year = currentViewDate.value.getFullYear();
    const month = currentViewDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);

    const daysInMonth = lastDayOfMonth.getDate();
    const startingDayOfWeek = firstDayOfMonth.getDay();

    const prevMonthDays = [];
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        prevMonthDays.push({
            day: prevMonthLastDay - i,
            currentMonth: false,
            date: new Date(year, month - 1, prevMonthLastDay - i)
        });
    }

    const currentMonthDays = [];
    for (let i = 1; i <= daysInMonth; i++) {
        currentMonthDays.push({
            day: i,
            currentMonth: true,
            date: new Date(year, month, i)
        });
    }

    const totalDisplayed = prevMonthDays.length + currentMonthDays.length;
    const nextMonthDaysNeeded = 42 - totalDisplayed;
    const nextMonthDays = [];
    for (let i = 1; i <= nextMonthDaysNeeded; i++) {
        nextMonthDays.push({
            day: i,
            currentMonth: false,
            date: new Date(year, month + 1, i)
        });
    }

    return [...prevMonthDays, ...currentMonthDays, ...nextMonthDays];
});

const isSelected = (date: Date) => {
    if (!props.modelValue) return false;
    const [year, month, day] = props.modelValue.split('-').map(Number);
    return date.getDate() === day &&
        date.getMonth() === (month - 1) &&
        date.getFullYear() === year;
};

const isToday = (date: Date) => {
    const today = new Date();
    return date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear();
};

// Methods
const prevMonth = () => {
    currentViewDate.value = new Date(currentViewDate.value.getFullYear(), currentViewDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentViewDate.value = new Date(currentViewDate.value.getFullYear(), currentViewDate.value.getMonth() + 1, 1);
};

const selectDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;

    emit('update:modelValue', dateString);
    isOpen.value = false;
};

const clearDate = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    emit('clear');
};

const setToday = () => {
    const today = new Date();
    currentViewDate.value = today;
    selectDate(today);
};

const handleClickOutside = (e: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="w-full relative" ref="containerRef">
        <div class="relative group cursor-pointer" @click="!disabled && (isOpen = !isOpen)">
            <input readonly :value="formattedDisplayDate" :placeholder="placeholder || 'dd/mm/aaaa'"
                :disabled="disabled"
                class="w-full border-0 border-b border-gray-300 focus:border-teal-500 focus:ring-0 px-0 py-2 bg-transparent transition-colors placeholder-gray-300 text-gray-900 cursor-pointer"
                :class="[
                    inputClass || 'text-base font-normal',
                    { 'border-red-500 focus:border-red-500': error },
                    { 'disabled:text-gray-400': disabled },
                ]" />

            <div class="absolute right-0 top-1/2 -translate-y-1/2 flex items-center gap-2">
                <button v-if="modelValue && !disabled" @click="clearDate"
                    class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                    <X class="w-4 h-4" />
                </button>
                <div class="text-gray-400 pointer-events-none group-hover:text-teal-600 transition-colors">
                    <CalendarIcon class="w-4 h-4" />
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>

        <div v-if="isOpen && !disabled"
            class="absolute z-50 mt-1 p-4 bg-white border border-gray-200 rounded-lg shadow-xl w-[280px] right-0 sm:left-0 sm:right-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-gray-900">
                    {{ currentMonthName }} de {{ currentYear }}
                </h3>
                <div class="flex gap-1">
                    <button @click.stop="prevMonth"
                        class="p-1 hover:bg-gray-100 rounded-full text-gray-600 transition-colors">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    <button @click.stop="nextMonth"
                        class="p-1 hover:bg-gray-100 rounded-full text-gray-600 transition-colors">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 mb-2">
                <span v-for="day in weekDays" :key="day" class="text-xs text-center font-medium text-gray-500 py-1">
                    {{ day }}
                </span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <button v-for="(dayObj, index) in calendarDays" :key="index" @click.stop="selectDate(dayObj.date)"
                    class="h-8 w-8 text-sm flex items-center justify-center rounded-md transition-all relative group"
                    :class="[
                        !dayObj.currentMonth ? 'text-gray-300' : 'text-gray-700 hover:bg-gray-100',
                        isSelected(dayObj.date) ? 'bg-teal-600 !text-white hover:!bg-teal-700 font-bold shadow-md' : '',
                        isToday(dayObj.date) && !isSelected(dayObj.date) ? 'text-teal-600 font-bold' : ''
                    ]">
                    {{ dayObj.day }}
                </button>
            </div>

            <div class="border-t border-gray-100 mt-3 pt-3 flex justify-between">
                <button @click.stop="clearDate"
                    class="text-xs text-teal-600 hover:text-teal-700 font-medium hover:underline">
                    Borrar
                </button>
                <button @click.stop="setToday"
                    class="text-xs text-teal-600 hover:text-teal-700 font-medium hover:underline">
                    Hoy
                </button>
            </div>
        </div>
    </div>
</template>
