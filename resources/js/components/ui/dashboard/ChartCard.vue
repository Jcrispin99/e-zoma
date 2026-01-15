<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = withDefaults(defineProps<{
    title?: string;
    type?: 'bar' | 'donut' | 'pie' | 'line' | 'area';
    series: any[];
    categories?: string[];
    labels?: string[];
    colors?: string[];
    height?: number;
    horizontal?: boolean;
}>(), {
    type: 'bar',
    height: 350,
    colors: () => ['#0d9488'],
    horizontal: false,
});

const chartOptions = computed(() => {
    const isPieDonut = ['pie', 'donut'].includes(props.type);

    const baseOptions: any = {
        chart: { type: props.type, height: props.height, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: props.colors,
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily: 'inherit' },
        plotOptions: {},
        xaxis: {
            labels: {
                style: { fontFamily: 'inherit' }
            }
        },
        yaxis: {
            labels: {
                style: { fontFamily: 'inherit' }
            }
        },
        tooltip: {
            custom: function ({ series, seriesIndex, dataPointIndex, w }: any) {
                let label, value;
                if (isPieDonut) {
                    label = w.globals.labels[seriesIndex];
                    value = series[seriesIndex];
                } else {
                    label = w.globals.labels[dataPointIndex];
                    value = series[seriesIndex][dataPointIndex];
                }

                return `<div class="bg-white p-2 border border-gray-200 shadow-lg rounded-md text-sm z-50 min-w-[120px]">
                    <div class="font-medium text-gray-500 mb-1 text-xs uppercase tracking-wider">${label}</div>
                    <div class="font-bold text-gray-800 text-lg">${Number(value).toLocaleString()}</div>
                </div>`;
            }
        }
    };

    if (props.type === 'bar') {
        baseOptions.plotOptions.bar = {
            borderRadius: 4,
            horizontal: props.horizontal,
            columnWidth: '45%',
            barHeight: '70%'
        };
        baseOptions.dataLabels.enabled = props.horizontal;
    }

    if (isPieDonut) {
        baseOptions.labels = props.labels || [];
        baseOptions.plotOptions.pie = {
            donut: { size: '65%' }
        };
    } else {
        baseOptions.xaxis.categories = props.categories || [];
    }

    return baseOptions;
});

const hasData = computed(() => {
    if (!props.series || props.series.length === 0) return false;

    if (['pie', 'donut'].includes(props.type)) {
        return props.series.some((val: any) => Number(val) > 0);
    }

    if (props.series[0] && Array.isArray(props.series[0].data)) {
        return props.series.some((s: any) => s.data && s.data.length > 0 && s.data.some((v: any) => Number(v) > 0));
    }

    return true;
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 h-full flex flex-col">
        <h3 v-if="title" class="text-lg font-semibold text-gray-800 mb-6">{{ title }}</h3>
        <div class="w-full flex-1 flex justify-center items-center min-h-[300px]">
            <VueApexCharts v-if="hasData" :type="type" :height="height" :options="chartOptions" :series="series"
                class="w-full" />
            <div v-else class="text-center text-gray-400 flex flex-col items-center justify-center h-full w-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-bar-chart-2 mb-3 opacity-50">
                    <line x1="18" x2="18" y1="20" y2="10" />
                    <line x1="12" x2="12" y1="20" y2="4" />
                    <line x1="6" x2="6" y1="20" y2="14" />
                </svg>
                <p class="text-sm font-medium">No hay datos para mostrar</p>
            </div>
        </div>
    </div>
</template>
