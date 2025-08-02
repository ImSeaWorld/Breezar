<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">{{ client.name }}</div>
                    <div class="text-caption text-grey-7">{{ client.fly_org_id }}</div>
                </div>
                <div>
                    <q-btn
                        v-if="$page.props.auth.user.role === 'admin'"
                        color="primary"
                        icon="mdi-pencil"
                        label="Edit"
                        @click="editClient"
                        class="q-mr-sm"
                    />
                    <q-btn
                        v-if="$page.props.auth.user.role === 'admin'"
                        color="negative"
                        icon="mdi-delete"
                        label="Delete"
                        @click="confirmDelete"
                    />
                </div>
            </div>

            <div class="row q-col-gutter-md">
                <!-- Client Details -->
                <div class="col-12 col-md-4">
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Client Details</div>
                            
                            <q-list>
                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Status</q-item-label>
                                        <q-item-label>
                                            <q-chip
                                                :color="client.status === 'active' ? 'positive' : 'negative'"
                                                text-color="white"
                                                size="sm"
                                                dense
                                            >
                                                {{ client.status }}
                                            </q-chip>
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item v-if="client.billing_start_date">
                                    <q-item-section>
                                        <q-item-label caption>Billing Start Date</q-item-label>
                                        <q-item-label>{{ formatDate(client.billing_start_date) }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item v-if="client.contact_info">
                                    <q-item-section>
                                        <q-item-label caption>Contact Information</q-item-label>
                                        <q-item-label v-if="client.contact_info.email">
                                            <q-icon name="mdi-email" size="xs" /> {{ client.contact_info.email }}
                                        </q-item-label>
                                        <q-item-label v-if="client.contact_info.phone">
                                            <q-icon name="mdi-phone" size="xs" /> {{ client.contact_info.phone }}
                                        </q-item-label>
                                        <q-item-label v-if="client.contact_info.address">
                                            <q-icon name="mdi-map-marker" size="xs" /> {{ client.contact_info.address }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>

                        <q-separator />

                        <q-card-section>
                            <div class="text-h6 q-mb-md">Assigned Users</div>
                            <q-list>
                                <q-item v-for="user in client.users" :key="user.id">
                                    <q-item-section avatar>
                                        <q-avatar color="primary" text-color="white" size="sm">
                                            {{ user.name.charAt(0) }}
                                        </q-avatar>
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label>{{ user.name }}</q-item-label>
                                        <q-item-label caption>{{ user.email }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                                <q-item v-if="client.users.length === 0">
                                    <q-item-section>
                                        <q-item-label class="text-grey-6">No users assigned</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>
                </div>

                <!-- Instances -->
                <div class="col-12 col-md-8">
                    <q-card>
                        <q-card-section>
                            <div class="row items-center justify-between q-mb-md">
                                <div class="text-h6">Instances</div>
                                <q-btn
                                    color="primary"
                                    size="sm"
                                    icon="mdi-sync"
                                    label="Sync"
                                    @click="syncInstances"
                                    :loading="syncing"
                                />
                            </div>

                            <q-table
                                :rows="client.instances"
                                :columns="instanceColumns"
                                row-key="id"
                                flat
                                bordered
                            >
                                <template v-slot:body-cell-type="props">
                                    <q-td :props="props">
                                        <q-chip size="sm" dense>
                                            {{ props.row.type.toUpperCase() }}
                                        </q-chip>
                                    </q-td>
                                </template>

                                <template v-slot:body-cell-status="props">
                                    <q-td :props="props">
                                        <q-chip
                                            :color="getStatusColor(props.row.status)"
                                            text-color="white"
                                            size="sm"
                                            dense
                                        >
                                            {{ props.row.status }}
                                        </q-chip>
                                    </q-td>
                                </template>

                                <template v-slot:body-cell-actions="props">
                                    <q-td :props="props">
                                        <q-btn
                                            size="sm"
                                            flat
                                            dense
                                            icon="mdi-eye"
                                            @click="viewInstance(props.row.id)"
                                        />
                                    </q-td>
                                </template>
                            </q-table>
                        </q-card-section>
                    </q-card>

                    <!-- Recent Reports -->
                    <q-card class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Reports</div>
                            <q-list>
                                <q-item v-for="report in client.reports" :key="report.id">
                                    <q-item-section>
                                        <q-item-label>{{ report.type }} Report</q-item-label>
                                        <q-item-label caption>
                                            {{ formatDate(report.report_month) }}
                                        </q-item-label>
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-btn
                                            size="sm"
                                            flat
                                            icon="mdi-eye"
                                            @click="viewReport(report.id)"
                                        />
                                    </q-item-section>
                                </q-item>
                                <q-item v-if="client.reports.length === 0">
                                    <q-item-section>
                                        <q-item-label class="text-grey-6">No reports available</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>

                    <!-- Recent Activities -->
                    <q-card class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Activities</div>
                            <q-list>
                                <q-item v-for="activity in recentActivities" :key="activity.id">
                                    <q-item-section avatar>
                                        <q-icon :name="getActivityIcon(activity.action)" />
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label>{{ formatActivity(activity) }}</q-item-label>
                                        <q-item-label caption>
                                            {{ formatDateTime(activity.created_at) }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>
                </div>
            </div>

            <!-- Delete Dialog -->
            <q-dialog v-model="showDeleteDialog">
                <q-card>
                    <q-card-section class="row items-center">
                        <q-icon name="mdi-alert" color="negative" size="2rem" />
                        <span class="q-ml-sm">
                            Are you sure you want to delete client "{{ client.name }}"?
                        </span>
                    </q-card-section>

                    <q-card-section>
                        This action cannot be undone. All associated data will be permanently deleted.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Delete"
                            color="negative"
                            @click="deleteClient"
                            :loading="deleting"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

export default {
    components: {
        AuthenticatedLayout,
    },

    props: {
        client: Object,
        recentActivities: Array,
    },

    data() {
        return {
            showDeleteDialog: false,
            deleting: false,
            syncing: false,
            instanceColumns: [
                { name: 'fly_app_id', label: 'App ID', field: 'fly_app_id', align: 'left' },
                { name: 'type', label: 'Type', field: 'type', align: 'center' },
                { name: 'region', label: 'Region', field: 'region', align: 'left' },
                { name: 'status', label: 'Status', field: 'status', align: 'center' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
        };
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        formatDateTime(dateTime) {
            return new Date(dateTime).toLocaleString();
        },

        formatActivity(activity) {
            const actions = {
                'client_created': 'created this client',
                'client_updated': 'updated this client',
                'fly_sync_completed': 'synced Fly.io instances',
                'fly_sync_failed': 'failed to sync Fly.io instances',
            };
            
            return `${activity.user.name} ${actions[activity.action] || activity.action}`;
        },

        getActivityIcon(action) {
            const icons = {
                'client_created': 'mdi-plus-circle',
                'client_updated': 'mdi-pencil',
                'fly_sync_completed': 'mdi-sync',
                'fly_sync_failed': 'mdi-sync-alert',
            };
            
            return icons[action] || 'mdi-information';
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

        editClient() {
            this.$inertia.visit(route('clients.edit', this.client.id));
        },

        confirmDelete() {
            this.showDeleteDialog = true;
        },

        deleteClient() {
            this.deleting = true;
            
            this.$inertia.delete(route('clients.destroy', this.client.id), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Client deleted successfully',
                    });
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to delete client',
                    });
                },
                onFinish: () => {
                    this.deleting = false;
                },
            });
        },

        syncInstances() {
            this.syncing = true;
            
            this.$inertia.post(route('fly-sync.run'), {
                client_id: this.client.id,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Sync job dispatched',
                    });
                },
                onFinish: () => {
                    this.syncing = false;
                },
            });
        },

        viewInstance(id) {
            this.$inertia.visit(route('instances.show', id));
        },

        viewReport(id) {
            this.$inertia.visit(route('reports.show', id));
        },
    },
};
</script>