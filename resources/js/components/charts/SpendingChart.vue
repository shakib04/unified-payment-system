<template>
    <div class="chart-container">
        <canvas ref="chartCanvas"></canvas>
    </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

export default {
    props: {
        data: {
            type: Array,
            required: true,
            default: () => []
        }
    },
    data() {
        return {
            chartInstance: null,
            chartColors: [
                '#4CAF50', // Green
                '#2196F3', // Blue
                '#FFC107', // Amber
                '#F44336', // Red
                '#9C27B0', // Purple
                '#3F51B5', // Indigo
                '#009688', // Teal
                '#FF5722', // Deep Orange
                '#795548', // Brown
                '#607D8B', // Blue Grey
                '#E91E63', // Pink
                '#673AB7', // Deep Purple
                '#00BCD4', // Cyan
                '#CDDC39', // Lime
                '#8BC34A'  // Light Green
            ]
        };
    },
    mounted() {
        this.createChart();
    },
    methods: {
        createChart() {
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }

            const canvas = this.$refs.chartCanvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Get the categories and amounts from the data
            const categories = this.data.map(item => item.category);
            const amounts = this.data.map(item => item.amount);

            // Create a color array based on data length
            const colors = this.chartColors.slice(0, this.data.length);

            this.chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categories,
                    datasets: [{
                        data: amounts,
                        backgroundColor: colors,
                        borderColor: colors.map(color => color),
                        borderWidth: 1,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ৳${value.toLocaleString('en-BD')} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        }
    },
    watch: {
        data: {
            handler() {
                this.$nextTick(() => {
                    this.createChart();
                });
            },
            deep: true
        }
    },
    beforeUnmount() {
        if (this.chartInstance) {
            this.chartInstance.destroy();
        }
    }
}
</script>

<style scoped>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>
