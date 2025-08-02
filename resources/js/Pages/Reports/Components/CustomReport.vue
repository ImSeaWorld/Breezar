<template>
    <div class="q-gutter-md">
        <!-- Client Information -->
        <q-card>
            <q-card-section>
                <div class="text-h6 q-mb-md">Client Information</div>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-list>
                            <q-item>
                                <q-item-section>
                                    <q-item-label caption>Client Name</q-item-label>
                                    <q-item-label>{{ data.client_name }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="data.billing_info">
                                <q-item-section>
                                    <q-item-label caption>Billing Start Date</q-item-label>
                                    <q-item-label>{{ formatDate(data.billing_info.billing_start_date) || 'Not Set' }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="data.billing_info">
                                <q-item-section>
                                    <q-item-label caption>Months Active</q-item-label>
                                    <q-item-label>{{ data.billing_info.months_active || 0 }} months</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </div>
                    <div class="col-12 col-md-6" v-if="data.contact_info">
                        <q-list>
                            <q-item v-if="data.contact_info.email">
                                <q-item-section>
                                    <q-item-label caption>Contact Email</q-item-label>
                                    <q-item-label>{{ data.contact_info.email }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="data.contact_info.phone">
                                <q-item-section>
                                    <q-item-label caption>Contact Phone</q-item-label>
                                    <q-item-label>{{ data.contact_info.phone }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="data.contact_info.address">
                                <q-item-section>
                                    <q-item-label caption>Contact Address</q-item-label>
                                    <q-item-label>{{ data.contact_info.address }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </div>
                </div>
            </q-card-section>
        </q-card>

        <!-- Instances Overview -->
        <q-card v-if="data.instances && data.instances.length > 0">
            <q-card-section>
                <div class="text-h6 q-mb-md">Instances Overview</div>
                
                <q-table
                    :rows="data.instances"
                    :columns="instanceColumns"
                    row-key="app_id"
                    flat
                    bordered
                >
                    <template v-slot:body-cell-type="props">
                        <q-td :props="props">
                            <q-chip size="sm" color="primary">
                                {{ props.row.type?.toUpperCase() || 'N/A' }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-status="props">
                        <q-td :props="props">
                            <q-chip
                                size="sm"
                                :color="getStatusColor(props.row.status)"
                                text-color="white"
                            >
                                {{ props.row.status }}
                            </q-chip>
                        </q-td>
                    </template>
                </q-table>
            </q-card-section>
        </q-card>

        <!-- Report Metadata -->
        <q-card>
            <q-card-section>
                <div class="text-h6 q-mb-md">Report Details</div>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <div class="text-caption text-grey-7">Report Period</div>
                        <div class="text-subtitle2">
                            {{ formatDate(data.period?.start) }} - {{ formatDate(data.period?.end) }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-caption text-grey-7">Generated At</div>
                        <div class="text-subtitle2">{{ formatDateTime(data.generated_at) }}</div>
                    </div>
                </div>
            </q-card-section>
        </q-card>

        <!-- Raw Data (for debugging/custom analysis) -->
        <q-expansion-item
            icon="mdi-code-json"
            label="Raw Report Data"
            class="q-mt-md"
        >
            <q-card>
                <q-card-section>
                    <pre class="q-pa-md bg-grey-2" style="overflow-x: auto;">{{ JSON.stringify(data, null, 2) }}</pre>
                </q-card-section>
            </q-card>
        </q-expansion-item>
    </div>
</template>

<script>
export default {
    props: {
        data: Object,
    },

    data() {
        return {
            instanceColumns: [
                { name: 'app_id', label: 'App ID', field: 'app_id', align: 'left' },
                { name: 'type', label: 'Type', field: 'type', align: 'center' },
                { name: 'region', label: 'Region', field: 'region', align: 'left' },
                { name: 'status', label: 'Status', field: 'status', align: 'center' },
            ],
        };
    },

    methods: {
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString();
        },

        formatDateTime(date) {
            if (!date) return '-';
            return new Date(date).toLocaleString();
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
    },
};
</script>