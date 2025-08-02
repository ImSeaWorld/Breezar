<template>
    <div class="q-gutter-md">
        <!-- Summary Card -->
        <q-card v-if="data.summary">
            <q-card-section>
                <div class="text-h6 q-mb-md">Work Items Summary</div>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section class="text-center">
                                <div class="text-h4 text-primary">{{ data.summary.total_activities }}</div>
                                <div class="text-subtitle2">Total Activities</div>
                            </q-card-section>
                        </q-card>
                    </div>
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section>
                                <div class="text-subtitle2 q-mb-sm">Activities by Action</div>
                                <div v-for="(count, action) in data.summary.by_action" :key="action" class="flex justify-between q-mb-xs">
                                    <span class="text-caption">{{ formatAction(action) }}</span>
                                    <q-badge :label="count" />
                                </div>
                            </q-card-section>
                        </q-card>
                    </div>
                    <div class="col-12 col-md-4">
                        <q-card flat bordered>
                            <q-card-section>
                                <div class="text-subtitle2 q-mb-sm">Activities by User</div>
                                <div v-for="(count, user) in data.summary.by_user" :key="user" class="flex justify-between q-mb-xs">
                                    <span class="text-caption">{{ user }}</span>
                                    <q-badge :label="count" />
                                </div>
                            </q-card-section>
                        </q-card>
                    </div>
                </div>
            </q-card-section>
        </q-card>

        <!-- Work Items Table -->
        <q-card v-if="data.work_items && data.work_items.length > 0">
            <q-card-section>
                <div class="text-h6 q-mb-md">Work Items Detail</div>
                
                <q-table
                    :rows="data.work_items"
                    :columns="columns"
                    row-key="date"
                    flat
                    bordered
                    :pagination="{ rowsPerPage: 20 }"
                >
                    <template v-slot:body-cell-action="props">
                        <q-td :props="props">
                            <q-chip size="sm" :color="getActionColor(props.row.action)">
                                {{ formatAction(props.row.action) }}
                            </q-chip>
                        </q-td>
                    </template>
                </q-table>
            </q-card-section>
        </q-card>

        <!-- Empty State -->
        <q-card v-if="!data.work_items || data.work_items.length === 0">
            <q-card-section class="text-center q-pa-lg">
                <q-icon name="mdi-clipboard-text-outline" size="48px" color="grey-6" />
                <div class="text-subtitle1 text-grey-6 q-mt-md">No work items found for this period</div>
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
                { name: 'date', label: 'Date/Time', field: 'date', align: 'left', sortable: true },
                { name: 'user', label: 'User', field: 'user', align: 'left' },
                { name: 'action', label: 'Action', field: 'action', align: 'center' },
                { name: 'description', label: 'Description', field: 'description', align: 'left' },
            ],
        };
    },

    methods: {
        formatAction(action) {
            const actions = {
                'client_created': 'Client Created',
                'client_updated': 'Client Updated',
                'instance_restarted': 'Instance Restarted',
                'instance_stopped': 'Instance Stopped',
                'instance_started': 'Instance Started',
                'fly_sync_completed': 'Fly Sync',
                'report_generated': 'Report Generated',
                'instance_viewed': 'Instance Viewed',
            };
            
            return actions[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        getActionColor(action) {
            const colors = {
                'client_created': 'positive',
                'client_updated': 'primary',
                'instance_restarted': 'warning',
                'instance_stopped': 'negative',
                'instance_started': 'positive',
                'fly_sync_completed': 'info',
                'report_generated': 'secondary',
            };
            
            return colors[action] || 'grey';
        },
    },
};
</script>