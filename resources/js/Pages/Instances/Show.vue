<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">Instance: {{ instance.fly_app_id }}</div>
                    <div class="text-caption text-grey-7">
                        <Link :href="route('clients.show', instance.client.id)" class="text-primary">
                            {{ instance.client.name }}
                        </Link>
                        | {{ instance.type.toUpperCase() }} | {{ instance.region }}
                    </div>
                </div>
                <div>
                    <q-btn-group push>
                        <q-btn
                            color="primary"
                            icon="mdi-restart"
                            label="Restart"
                            @click="confirmRestart"
                            :disable="instance.status !== 'running'"
                        />
                        <q-btn
                            color="negative"
                            icon="mdi-stop"
                            label="Stop"
                            @click="confirmStop"
                            :disable="instance.status === 'stopped'"
                        />
                        <q-btn
                            color="positive"
                            icon="mdi-play"
                            label="Start"
                            @click="confirmStart"
                            :disable="instance.status === 'running'"
                        />
                        <q-btn
                            color="secondary"
                            icon="mdi-console"
                            label="Console"
                            @click="openConsole"
                        />
                    </q-btn-group>
                </div>
            </div>

            <div class="row q-col-gutter-md">
                <!-- Instance Details -->
                <div class="col-12 col-md-4">
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Instance Details</div>
                            
                            <q-list>
                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Status</q-item-label>
                                        <q-item-label>
                                            <q-chip
                                                :color="getStatusColor(instance.status)"
                                                text-color="white"
                                                size="sm"
                                                dense
                                            >
                                                {{ instance.status }}
                                            </q-chip>
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Type</q-item-label>
                                        <q-item-label>{{ instance.type.toUpperCase() }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Region</q-item-label>
                                        <q-item-label>{{ instance.region }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Size</q-item-label>
                                        <q-item-label>{{ instance.size || 'Default' }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Created</q-item-label>
                                        <q-item-label>{{ formatDate(instance.created_at) }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Last Updated</q-item-label>
                                        <q-item-label>{{ formatDate(instance.updated_at) }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>

                    <!-- Metadata -->
                    <q-card v-if="instance.metadata && Object.keys(instance.metadata).length > 0" class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Metadata</div>
                            <pre class="q-ma-none text-caption">{{ JSON.stringify(instance.metadata, null, 2) }}</pre>
                        </q-card-section>
                    </q-card>
                </div>

                <!-- Machines & Metrics -->
                <div class="col-12 col-md-8">
                    <!-- Console Access Info -->
                    <q-banner class="bg-info text-white q-mb-md" icon="mdi-information">
                        <div class="text-weight-medium">Console Access</div>
                        <div class="text-caption">
                            Direct console access is not available through the Fly.io GraphQL API. 
                            Use the Fly CLI command: <code class="text-white">fly ssh console -a {{ instance.fly_app_id }}</code>
                        </div>
                    </q-banner>
                    
                    <!-- Machines -->
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Machines</div>
                            
                            <q-table
                                :rows="machines"
                                :columns="machineColumns"
                                row-key="id"
                                flat
                                bordered
                                :pagination="{ rowsPerPage: 10 }"
                            >
                                <template v-slot:body-cell-state="props">
                                    <q-td :props="props">
                                        <q-chip
                                            :color="getMachineStateColor(props.row.state)"
                                            text-color="white"
                                            size="sm"
                                            dense
                                        >
                                            {{ props.row.state }}
                                        </q-chip>
                                    </q-td>
                                </template>

                                <template v-slot:body-cell-region="props">
                                    <q-td :props="props">
                                        {{ props.row.region }}
                                    </q-td>
                                </template>

                                <template v-slot:body-cell-createdAt="props">
                                    <q-td :props="props">
                                        {{ formatDate(props.row.createdAt) }}
                                    </q-td>
                                </template>
                                <template v-slot:body-cell-actions="props">
                                    <q-td :props="props">
                                        <q-btn
                                            size="sm"
                                            flat
                                            dense
                                            icon="mdi-console"
                                            color="grey"
                                            disable
                                        >
                                            <q-tooltip>
                                                Console not available via API<br>
                                                Use: fly ssh console -a {{ instance.fly_app_id }}
                                            </q-tooltip>
                                        </q-btn>
                                    </q-td>
                                </template>
                            </q-table>
                        </q-card-section>
                    </q-card>

                    <!-- Logs -->
                    <q-card class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Logs</div>
                            
                            <div class="log-container q-pa-sm bg-grey-10 text-white" style="height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                <div v-for="(log, index) in logs" :key="index" class="log-line">
                                    <span class="text-grey-5">{{ formatLogTimestamp(log.timestamp) }}</span>
                                    <span :class="getLogLevelClass(log.level)">[{{ log.level }}]</span>
                                    <span class="text-cyan-3">[{{ log.allocationIdShort || log.instanceId }}]</span>
                                    {{ log.message }}
                                </div>
                                <div v-if="!logs || logs.length === 0" class="text-grey-5">
                                    <div>No logs available</div>
                                    <div class="text-caption q-mt-sm">
                                        This could be because:
                                        <ul class="q-mt-xs q-mb-none q-pl-md">
                                            <li>The app has no recent activity</li>
                                            <li>Logs are not available via GraphQL API</li>
                                            <li>The app uses a different logging mechanism</li>
                                        </ul>
                                        <div class="q-mt-sm">
                                            Try: <code>fly logs -a {{ instance.fly_app_id }}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>

                    <!-- Metrics -->
                    <q-card class="q-mt-md" v-if="metrics">
                        <q-card-section>
                            <div class="row items-center justify-between q-mb-md">
                                <div class="text-h6">Metrics</div>
                                <div>
                                    <q-btn
                                        flat
                                        dense
                                        icon="mdi-refresh"
                                        @click="refreshMetrics"
                                        :loading="metricsLoading"
                                    />
                                    <q-btn
                                        flat
                                        dense
                                        icon="mdi-open-in-new"
                                        @click="openGrafana"
                                        title="Open in Grafana"
                                    />
                                </div>
                            </div>
                            
                            <!-- Current Metrics Summary -->
                            <div class="row q-col-gutter-md q-mb-md" v-if="!metrics.error">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <q-card flat bordered>
                                        <q-card-section class="text-center">
                                            <div class="text-subtitle2 text-grey-7">CPU Usage</div>
                                            <div class="text-h4 text-primary">{{ formatCpuMetric(metrics.cpu) }}%</div>
                                        </q-card-section>
                                    </q-card>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <q-card flat bordered>
                                        <q-card-section class="text-center">
                                            <div class="text-subtitle2 text-grey-7">Memory Usage</div>
                                            <div class="text-h4 text-primary">{{ formatMemoryMetric(metrics.memory) }}%</div>
                                        </q-card-section>
                                    </q-card>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <q-card flat bordered>
                                        <q-card-section class="text-center">
                                            <div class="text-subtitle2 text-grey-7">Network In</div>
                                            <div class="text-h4 text-positive">{{ formatNetworkMetric(metrics.network?.rx) }}</div>
                                        </q-card-section>
                                    </q-card>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <q-card flat bordered>
                                        <q-card-section class="text-center">
                                            <div class="text-subtitle2 text-grey-7">Network Out</div>
                                            <div class="text-h4 text-warning">{{ formatNetworkMetric(metrics.network?.tx) }}</div>
                                        </q-card-section>
                                    </q-card>
                                </div>
                            </div>
                            
                            <div v-else class="text-negative q-pa-md text-center">
                                <q-icon name="mdi-alert-circle" size="24px" class="q-mr-sm" />
                                {{ metrics.error }}
                            </div>
                            
                            <!-- Metrics Charts -->
                            <q-tabs v-model="metricsTab" dense align="left" class="text-grey-7">
                                <q-tab name="cpu" label="CPU" />
                                <q-tab name="memory" label="Memory" />
                                <q-tab name="network" label="Network" />
                                <q-tab name="http" label="HTTP" />
                            </q-tabs>
                            
                            <q-separator />
                            
                            <q-tab-panels v-model="metricsTab" animated>
                                <q-tab-panel name="cpu">
                                    <MetricsChart
                                        title="CPU Usage"
                                        metric="cpu"
                                        :instance-id="instance.id"
                                        unit="%"
                                        color="#1976d2"
                                    />
                                </q-tab-panel>
                                
                                <q-tab-panel name="memory">
                                    <MetricsChart
                                        title="Memory Usage"
                                        metric="memory"
                                        :instance-id="instance.id"
                                        unit="%"
                                        color="#388e3c"
                                    />
                                </q-tab-panel>
                                
                                <q-tab-panel name="network">
                                    <div class="q-gutter-md">
                                        <MetricsChart
                                            title="Network Received"
                                            metric="network_rx"
                                            :instance-id="instance.id"
                                            unit="bytes"
                                            color="#00897b"
                                        />
                                        <MetricsChart
                                            title="Network Sent"
                                            metric="network_tx"
                                            :instance-id="instance.id"
                                            unit="bytes"
                                            color="#f57c00"
                                        />
                                    </div>
                                </q-tab-panel>
                                
                                <q-tab-panel name="http">
                                    <div class="q-gutter-md">
                                        <MetricsChart
                                            title="HTTP Requests"
                                            metric="http_requests"
                                            :instance-id="instance.id"
                                            unit="requests"
                                            color="#7b1fa2"
                                        />
                                        <MetricsChart
                                            title="HTTP Errors (5xx)"
                                            metric="http_errors"
                                            :instance-id="instance.id"
                                            unit="requests"
                                            color="#d32f2f"
                                        />
                                    </div>
                                </q-tab-panel>
                            </q-tab-panels>
                        </q-card-section>
                    </q-card>
                </div>
            </div>

            <!-- Action Dialogs -->
            <q-dialog v-model="showRestartDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Restart Instance</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to restart this instance? All machines will be restarted.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Restart"
                            color="primary"
                            @click="restartInstance"
                            :loading="actionLoading"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>

            <q-dialog v-model="showStopDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Stop Instance</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to stop this instance? All machines will be stopped.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Stop"
                            color="negative"
                            @click="stopInstance"
                            :loading="actionLoading"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>

            <q-dialog v-model="showStartDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Start Instance</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to start this instance? All machines will be started.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Start"
                            color="positive"
                            @click="startInstance"
                            :loading="actionLoading"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Link } from '@inertiajs/vue3';
import MetricsChart from '@/Components/MetricsChart.vue';

export default {
    components: {
        AuthenticatedLayout,
        Link,
        MetricsChart,
    },

    props: {
        instance: Object,
        machines: Array,
        logs: Array,
        metrics: Object,
    },

    data() {
        return {
            showRestartDialog: false,
            showStopDialog: false,
            showStartDialog: false,
            actionLoading: false,
            metricsLoading: false,
            metricsTab: 'cpu',
            grafanaUrls: null,
            machineColumns: [
                { name: 'id', label: 'Machine ID', field: 'id', align: 'left' },
                { name: 'state', label: 'State', field: 'state', align: 'center' },
                { name: 'region', label: 'Region', field: 'region', align: 'left' },
                { name: 'createdAt', label: 'Created', field: 'createdAt', align: 'left' },
            ],
        };
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        formatMetric(value) {
            return value ? value.toFixed(2) : '0.00';
        },

        formatLogTimestamp(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toTimeString().split(' ')[0]; // HH:MM:SS format
        },

        getLogLevelClass(level) {
            const classes = {
                'error': 'text-red',
                'warn': 'text-orange',
                'warning': 'text-orange',
                'info': 'text-blue',
                'debug': 'text-grey-6',
            };
            return classes[level?.toLowerCase()] || 'text-white';
        },

        getStatusColor(status) {
            const colors = {
                'running': 'positive',
                'stopped': 'negative',
                'partial': 'warning',
                'not_deployed': 'grey',
                'no_machines': 'grey',
            };
            
            return colors[status] || 'grey';
        },

        getMachineStateColor(state) {
            const colors = {
                'started': 'positive',
                'stopped': 'negative',
                'stopping': 'warning',
                'starting': 'info',
                'destroyed': 'grey',
            };
            
            return colors[state] || 'grey';
        },

        confirmRestart() {
            this.showRestartDialog = true;
        },

        confirmStop() {
            this.showStopDialog = true;
        },

        confirmStart() {
            this.showStartDialog = true;
        },

        restartInstance() {
            this.actionLoading = true;
            
            this.$inertia.post(route('instances.restart', this.instance.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Instance restart initiated',
                    });
                    this.showRestartDialog = false;
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to restart instance',
                    });
                },
                onFinish: () => {
                    this.actionLoading = false;
                },
            });
        },

        stopInstance() {
            this.actionLoading = true;
            
            this.$inertia.post(route('instances.stop', this.instance.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Instance stopped',
                    });
                    this.showStopDialog = false;
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to stop instance',
                    });
                },
                onFinish: () => {
                    this.actionLoading = false;
                },
            });
        },

        startInstance() {
            this.actionLoading = true;
            
            this.$inertia.post(route('instances.start', this.instance.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Instance started',
                    });
                    this.showStartDialog = false;
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to start instance',
                    });
                },
                onFinish: () => {
                    this.actionLoading = false;
                },
            });
        },
        
        formatCpuMetric(cpuData) {
            if (!cpuData || cpuData.length === 0) return '0.0';
            return (cpuData[0]?.value || 0).toFixed(1);
        },
        
        formatMemoryMetric(memoryData) {
            if (!memoryData || !memoryData.percentage) return '0.0';
            return memoryData.percentage.toFixed(1);
        },
        
        formatNetworkMetric(networkData) {
            if (!networkData || networkData.length === 0) return '0 B/s';
            const bytesPerSec = networkData[0]?.value || 0;
            return this.formatBytes(bytesPerSec) + '/s';
        },
        
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        async refreshMetrics() {
            this.metricsLoading = true;
            
            try {
                const response = await fetch(route('instances.metrics', this.instance.id));
                if (!response.ok) {
                    throw new Error('Failed to fetch metrics');
                }
                
                this.metrics = await response.json();
            } catch (error) {
                this.$q.notify({
                    type: 'negative',
                    message: 'Failed to refresh metrics',
                });
            } finally {
                this.metricsLoading = false;
            }
        },
        
        async openGrafana() {
            if (!this.grafanaUrls) {
                // Fetch Grafana URLs if not already loaded
                try {
                    const response = await fetch(route('instances.grafana-urls', this.instance.id));
                    if (!response.ok) {
                        throw new Error('Failed to fetch Grafana URLs');
                    }
                    
                    this.grafanaUrls = await response.json();
                } catch (error) {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to get Grafana URL',
                    });
                    return;
                }
            }
            
            if (this.grafanaUrls?.dashboard) {
                window.open(this.grafanaUrls.dashboard, '_blank');
            }
        },

    },

    mounted() {
        // Debug logging for logs
        console.log('Instance Show mounted - logs:', this.logs);
        console.log('Instance Show mounted - logs type:', typeof this.logs);
        console.log('Instance Show mounted - logs count:', this.logs ? this.logs.length : 'null/undefined');
        
        // Check for flash error messages when component loads
        const errorMessage = this.$page.props.flash?.error;
        if (errorMessage) {
            this.$q.notify({
                type: 'negative',
                message: errorMessage,
                timeout: 5000,
            });
        }

        const successMessage = this.$page.props.flash?.success;
        if (successMessage) {
            this.$q.notify({
                type: 'positive',
                message: successMessage,
                timeout: 3000,
            });
        }
    },
};
</script>

<style scoped>
.log-container {
    border-radius: 4px;
}

.log-line {
    white-space: pre-wrap;
    word-break: break-all;
    line-height: 1.4;
}
</style>