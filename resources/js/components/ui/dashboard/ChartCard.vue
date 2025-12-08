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
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 h-full">
        <h3 v-if="title" class="text-lg font-semibold text-gray-800 mb-6">{{ title }}</h3>
        <div class="w-full flex justify-center items-center min-h-[300px]">
            <VueApexCharts :type="type" :height="height" :options="chartOptions" :series="series" class="w-full" />
        </div>
    </div>
</template>
