<template>
    <div class="q-gutter-md">
        <!-- Summary Card -->
        <q-card v-if="data.summary">
            <q-card-section>
                <div class="text-h6 q-mb-md">Performance Summary</div>
                <div class="text-caption text-grey-7">
                    Period: {{ formatDate(data.summary.period.start) }} - {{ formatDate(data.summary.period.end) }}
                </div>
                <div class="text-subtitle2 q-mt-sm">
                    Total Instances Monitored: {{ data.summary.total_instances }}
                </div>
            </q-card-section>
        </q-card>

        <!-- Instance Performance -->
        <q-card v-if="data.instances && data.instances.length > 0">
            <q-card-section>
                <div class="text-h6 q-mb-md">Instance Performance Metrics</div>
                
                <q-table
                    :rows="data.instances"
                    :columns="columns"
                    row-key="app_id"
                    flat
                    bordered
                >
                    <template v-slot:body-cell-app_id="props">
                        <q-td :props="props">
                            <div class="text-weight-medium">{{ props.row.app_id }}</div>
                            <div class="text-caption text-grey-7">{{ props.row.type?.toUpperCase() || 'N/A' }}</div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-avg_response_time="props">
                        <q-td :props="props">
                            <div v-if="props.row.error" class="text-negative">Error</div>
                            <div v-else>
                                <q-chip
                                    :color="getResponseTimeColor(props.row.avg_response_time)"
                                    text-color="white"
                                    size="sm"
                                >
                                    {{ props.row.avg_response_time || 0 }}ms
                                </q-chip>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-error_rate="props">
                        <q-td :props="props">
                            <div v-if="props.row.error" class="text-negative">-</div>
                            <div v-else>
                                <q-chip
                                    :color="getErrorRateColor(props.row.error_rate)"
                                    text-color="white"
                                    size="sm"
                                >
                                    {{ (props.row.error_rate || 0).toFixed(2) }}%
                                </q-chip>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-requests_per_minute="props">
                        <q-td :props="props">
                            <div v-if="props.row.error" class="text-negative">-</div>
                            <div v-else>{{ props.row.requests_per_minute || 0 }}</div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-availability="props">
                        <q-td :props="props">
                            <div v-if="props.row.error" class="text-negative">-</div>
                            <div v-else>
                                <q-circular-progress
                                    :value="props.row.availability || 100"
                                    size="50px"
                                    :thickness="0.2"
                                    :color="getAvailabilityColor(props.row.availability)"
                                    track-color="grey-3"
                                    class="q-ma-md"
                                >
                                    {{ (props.row.availability || 100).toFixed(1) }}%
                                </q-circular-progress>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-error="props">
                        <q-td :props="props">
                            <q-tooltip v-if="props.row.error">
                                {{ props.row.error }}
                            </q-tooltip>
                            <q-icon v-if="props.row.error" name="mdi-alert" color="negative" />
                        </q-td>
                    </template>
                </q-table>
            </q-card-section>
        </q-card>

        <!-- Performance Charts (placeholder for future implementation) -->
        <q-card>
            <q-card-section>
                <div class="text-h6 q-mb-md">Performance Trends</div>
                <div class="text-center q-pa-lg text-grey-6">
                    <q-icon name="mdi-chart-line" size="48px" />
                    <div class="q-mt-md">Performance charts will be available in a future update</div>
                </div>
            </q-card-section>
        </q-card>
    </div>
</template>

<script>
export default {
    props: {
        data: Object,
    },

    data() {
        return {
            columns: [
                { name: 'app_id', label: 'Instance', field: 'app_id', align: 'left' },
                { name: 'avg_response_time', label: 'Avg Response Time', field: 'avg_response_time', align: 'center' },
                { name: 'error_rate', label: 'Error Rate', field: 'error_rate', align: 'center' },
                { name: 'requests_per_minute', label: 'Requests/Min', field: 'requests_per_minute', align: 'center' },
                { name: 'availability', label: 'Availability', field: 'availability', align: 'center' },
            ],
        };
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        getResponseTimeColor(time) {
            if (time < 200) return 'positive';
            if (time < 500) return 'warning';
            return 'negative';
        },

        getErrorRateColor(rate) {
            if (rate < 1) return 'positive';
            if (rate < 5) return 'warning';
            return 'negative';
        },

        getAvailabilityColor(availability) {
            if (availability >= 99.9) return 'positive';
            if (availability >= 95) return 'warning';
            return 'negative';
        },
    },
};
</script>