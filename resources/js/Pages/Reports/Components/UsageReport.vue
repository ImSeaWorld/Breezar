<template>
    <div class="q-gutter-md">
        <!-- Summary Card -->
        <q-card>
            <q-card-section>
                <div class="text-h6 q-mb-md">Usage Summary</div>
                <div class="row q-col-gutter-md" v-if="data.summary">
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section class="text-center">
                                <div class="text-h4 text-primary">{{ data.summary.total_instances }}</div>
                                <div class="text-subtitle2">Total Instances</div>
                            </q-card-section>
                        </q-card>
                    </div>
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section class="text-center">
                                <div class="text-h4 text-positive">{{ data.summary.active_instances }}</div>
                                <div class="text-subtitle2">Active Instances</div>
                            </q-card-section>
                        </q-card>
                    </div>
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section class="text-center">
                                <div class="text-caption">Report Period</div>
                                <div class="text-subtitle2">
                                    {{ formatDate(data.summary.period.start) }} - 
                                    {{ formatDate(data.summary.period.end) }}
                                </div>
                            </q-card-section>
                        </q-card>
                    </div>
                </div>
            </q-card-section>
        </q-card>

        <!-- Instance Details -->
        <q-card v-if="data.instances && data.instances.length > 0">
            <q-card-section>
                <div class="text-h6 q-mb-md">Instance Usage Details</div>
                
                <q-expansion-item
                    v-for="(instance, index) in data.instances"
                    :key="index"
                    :label="instance.app_id"
                    :caption="`${instance.type?.toUpperCase() || 'N/A'} - ${instance.region || 'N/A'}`"
                    group="instances"
                    class="q-mb-sm"
                >
                    <q-card>
                        <q-card-section>
                            <div v-if="instance.error" class="text-negative">
                                {{ instance.error }}
                            </div>
                            <div v-else>
                                <div class="row q-col-gutter-md">
                                    <div class="col-12 col-md-6">
                                        <div class="text-subtitle2 q-mb-sm">CPU Usage</div>
                                        <q-linear-progress
                                            :value="getAverageValue(instance.cpu_usage)"
                                            size="25px"
                                            :color="getUsageColor(getAverageValue(instance.cpu_usage))"
                                            class="q-mb-md"
                                        >
                                            <div class="absolute-full flex flex-center">
                                                <q-badge color="white" text-color="black" :label="`${(getAverageValue(instance.cpu_usage) * 100).toFixed(1)}%`" />
                                            </div>
                                        </q-linear-progress>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="text-subtitle2 q-mb-sm">Memory Usage</div>
                                        <q-linear-progress
                                            :value="getAverageValue(instance.memory_usage)"
                                            size="25px"
                                            :color="getUsageColor(getAverageValue(instance.memory_usage))"
                                            class="q-mb-md"
                                        >
                                            <div class="absolute-full flex flex-center">
                                                <q-badge color="white" text-color="black" :label="`${(getAverageValue(instance.memory_usage) * 100).toFixed(1)}%`" />
                                            </div>
                                        </q-linear-progress>
                                    </div>

                                    <div class="col-12" v-if="instance.uptime">
                                        <div class="text-subtitle2">Uptime: {{ formatUptime(instance.uptime) }}</div>
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>
            </q-card-section>
        </q-card>
    </div>
</template>

<script>
export default {
    props: {
        data: Object,
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        getAverageValue(values) {
            if (!values || !Array.isArray(values) || values.length === 0) return 0;
            const sum = values.reduce((acc, val) => acc + val, 0);
            return sum / values.length / 100; // Convert percentage to decimal
        },

        getUsageColor(value) {
            if (value < 0.5) return 'positive';
            if (value < 0.8) return 'warning';
            return 'negative';
        },

        formatUptime(seconds) {
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            return `${days} days, ${hours} hours`;
        },
    },
};
</script>