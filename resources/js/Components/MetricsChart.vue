<template>
    <div class="metrics-chart">
        <div class="chart-header row items-center justify-between q-mb-sm">
            <div class="text-subtitle2">{{ title }}</div>
            <div>
                <q-btn-group flat dense>
                    <q-btn
                        v-for="dur in durations"
                        :key="dur.value"
                        :label="dur.label"
                        size="sm"
                        :outline="duration !== dur.value"
                        :unelevated="duration === dur.value"
                        @click="changeDuration(dur.value)"
                    />
                </q-btn-group>
            </div>
        </div>
        
        <div v-if="loading" class="chart-loading">
            <q-spinner-dots size="40px" color="primary" />
        </div>
        
        <div v-else-if="error" class="chart-error text-negative">
            <q-icon name="mdi-alert-circle" size="24px" class="q-mr-sm" />
            {{ error }}
        </div>
        
        <canvas v-else ref="chartCanvas" :height="height"></canvas>
    </div>
</template>

<script>
export default {
    name: 'MetricsChart',
    props: {
        title: {
            type: String,
            required: true
        },
        metric: {
            type: String,
            required: true
        },
        instanceId: {
            type: Number,
            required: true
        },
        height: {
            type: Number,
            default: 200
        },
        unit: {
            type: String,
            default: ''
        },
        color: {
            type: String,
            default: '#1976d2'
        }
    },
    
    data() {
        return {
            chart: null,
            loading: false,
            error: null,
            duration: '1h',
            data: [],
            durations: [
                { label: '15m', value: '15m' },
                { label: '1h', value: '1h' },
                { label: '6h', value: '6h' },
                { label: '24h', value: '24h' },
                { label: '7d', value: '7d' },
            ]
        };
    },
    
    mounted() {
        this.loadChartLibrary();
    },
    
    beforeUnmount() {
        if (this.chart) {
            this.chart.destroy();
        }
    },
    
    methods: {
        async loadChartLibrary() {
            // Dynamically load Chart.js if not already loaded
            if (!window.Chart) {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js';
                script.onload = () => {
                    this.initChart();
                };
                document.head.appendChild(script);
            } else {
                this.initChart();
            }
        },
        
        initChart() {
            this.fetchData();
        },
        
        async fetchData() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch(route('instances.metrics.timeseries', {
                    instance: this.instanceId,
                    metric: this.metric,
                    duration: this.duration,
                    step: this.getStep()
                }));
                
                if (!response.ok) {
                    throw new Error('Failed to fetch metrics');
                }
                
                const result = await response.json();
                this.data = result.data || [];
                
                this.renderChart();
            } catch (err) {
                this.error = err.message;
                console.error('Failed to fetch metrics:', err);
            } finally {
                this.loading = false;
            }
        },
        
        renderChart() {
            if (!this.$refs.chartCanvas || !window.Chart) {
                return;
            }
            
            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }
            
            // Prepare data
            const datasets = this.data.map((series, index) => {
                const label = this.formatLabel(series.labels);
                const data = series.values.map(point => ({
                    x: new Date(point.timestamp * 1000),
                    y: point.value
                }));
                
                return {
                    label: label,
                    data: data,
                    borderColor: this.getColor(index),
                    backgroundColor: this.getColor(index) + '20',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                };
            });
            
            const ctx = this.$refs.chartCanvas.getContext('2d');
            this.chart = new window.Chart(ctx, {
                type: 'line',
                data: {
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: datasets.length > 1,
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += this.formatValue(context.parsed.y);
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    minute: 'HH:mm',
                                    hour: 'HH:mm',
                                    day: 'MMM dd',
                                }
                            },
                            grid: {
                                display: false,
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => this.formatValue(value)
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                            }
                        }
                    }
                }
            });
        },
        
        formatLabel(labels) {
            // Extract meaningful label from metric labels
            if (labels.instance) {
                return labels.instance;
            }
            if (labels.status) {
                return `Status ${labels.status}`;
            }
            return this.title;
        },
        
        formatValue(value) {
            if (this.unit === '%') {
                return value.toFixed(1) + '%';
            } else if (this.unit === 'bytes') {
                return this.formatBytes(value);
            } else if (this.unit === 'requests') {
                return value.toFixed(0) + ' req/s';
            } else if (this.unit === 'seconds') {
                return value.toFixed(3) + 's';
            }
            return value.toFixed(2);
        },
        
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        getColor(index) {
            const colors = [
                '#1976d2', // blue
                '#388e3c', // green
                '#f57c00', // orange
                '#d32f2f', // red
                '#7b1fa2', // purple
                '#455a64', // blue-grey
            ];
            return colors[index % colors.length];
        },
        
        getStep() {
            // Calculate appropriate step based on duration
            const steps = {
                '15m': '15s',
                '1h': '1m',
                '6h': '5m',
                '24h': '15m',
                '7d': '1h',
            };
            return steps[this.duration] || '1m';
        },
        
        changeDuration(newDuration) {
            this.duration = newDuration;
            this.fetchData();
        }
    }
}
</script>

<style scoped>
.metrics-chart {
    position: relative;
    padding: 16px;
}

.chart-loading, .chart-error {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
}

/* Dark mode adjustments */
body.body--dark canvas {
    filter: invert(1) hue-rotate(180deg);
}
</style>