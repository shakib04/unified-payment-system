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
            this.chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.data.map(item => item.month),
                    datasets: [
                        {
                            label: 'Income',
                            data: this.data.map(item => item.income),
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Expenses',
                            data: this.data.map(item => item.expenses),
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Savings',
                            data: this.data.map(item => item.income - item.expenses),
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '৳ ' + value.toLocaleString('en-BD');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '৳ ' + context.parsed.y.toLocaleString('en-BD');
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false,
                            text: 'Monthly Financial Overview'
                        }
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
