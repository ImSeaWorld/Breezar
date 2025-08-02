<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Instances</div>
                <q-btn
                    color="primary"
                    icon="mdi-sync"
                    label="Sync All"
                    @click="syncAll"
                    v-if="$page.props.auth.user.role === 'admin'"
                />
            </div>

            <!-- Filters -->
            <q-card>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-3">
                            <q-input
                                v-model="filters.search"
                                label="Search instances"
                                filled
                                clearable
                                @update:model-value="debouncedSearch"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="mdi-magnify" />
                                </template>
                            </q-input>
                        </div>

                        <div class="col-12 col-md-3">
                            <q-select
                                v-model="filters.type"
                                :options="typeOptions"
                                label="Filter by Type"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-3">
                            <q-select
                                v-model="filters.status"
                                :options="statusOptions"
                                label="Filter by Status"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <!-- Instances Table -->
            <q-card>
                <q-table
                    :rows="instances.data"
                    :columns="columns"
                    row-key="id"
                    flat
                    :pagination="tablePagination"
                    @request="onRequest"
                >
                    <template v-slot:body-cell-fly_app_id="props">
                        <q-td :props="props">
                            <div>
                                <div class="text-weight-medium">{{ props.row.fly_app_id }}</div>
                                <div class="text-caption text-grey-7">
                                    <Link :href="route('clients.show', props.row.client.id)" class="text-primary">
                                        {{ props.row.client.name }}
                                    </Link>
                                </div>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-type="props">
                        <q-td :props="props">
                            <q-chip size="sm" color="primary">
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
                            >
                                {{ props.row.status }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-created_at="props">
                        <q-td :props="props">
                            {{ formatDate(props.row.created_at) }}
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
                            >
                                <q-tooltip>View Details</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-restart"
                                color="warning"
                                @click="confirmRestart(props.row)"
                                :disable="props.row.status !== 'running'"
                            >
                                <q-tooltip>Restart</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-stop"
                                color="negative"
                                @click="confirmStop(props.row)"
                                :disable="props.row.status === 'stopped'"
                            >
                                <q-tooltip>Stop</q-tooltip>
                            </q-btn>
                        </q-td>
                    </template>
                </q-table>
            </q-card>

            <!-- Restart Dialog -->
            <q-dialog v-model="showRestartDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Restart Instance</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to restart "{{ selectedInstance?.fly_app_id }}"?
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Restart"
                            color="warning"
                            @click="restartInstance"
                            :loading="actionLoading"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>

            <!-- Stop Dialog -->
            <q-dialog v-model="showStopDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Stop Instance</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to stop "{{ selectedInstance?.fly_app_id }}"?
                        All machines will be stopped.
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
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { debounce } from 'quasar';
import { Link } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Link,
    },

    props: {
        instances: Object,
        filters: Object,
    },

    data() {
        return {
            showRestartDialog: false,
            showStopDialog: false,
            selectedInstance: null,
            actionLoading: false,
            columns: [
                { name: 'fly_app_id', label: 'Instance', field: 'fly_app_id', align: 'left' },
                { name: 'type', label: 'Type', field: 'type', align: 'center' },
                { name: 'region', label: 'Region', field: 'region', align: 'left' },
                { name: 'status', label: 'Status', field: 'status', align: 'center' },
                { name: 'created_at', label: 'Created', field: 'created_at', align: 'left' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
            typeOptions: [
                { label: 'SQL', value: 'sql' },
                { label: 'SAS', value: 'sas' },
            ],
            statusOptions: [
                { label: 'Running', value: 'running' },
                { label: 'Stopped', value: 'stopped' },
                { label: 'Partial', value: 'partial' },
                { label: 'Not Deployed', value: 'not_deployed' },
                { label: 'No Machines', value: 'no_machines' },
            ],
        };
    },

    computed: {
        tablePagination() {
            return {
                page: this.instances.current_page,
                rowsPerPage: this.instances.per_page,
                rowsNumber: this.instances.total,
            };
        },
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
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

        applyFilters() {
            this.$inertia.get(route('instances.index'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        debouncedSearch: debounce(function() {
            this.applyFilters();
        }, 500),

        onRequest(props) {
            const { page } = props.pagination;
            
            this.$inertia.get(route('instances.index'), {
                ...this.filters,
                page,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        viewInstance(id) {
            this.$inertia.visit(route('instances.show', id));
        },

        confirmRestart(instance) {
            this.selectedInstance = instance;
            this.showRestartDialog = true;
        },

        confirmStop(instance) {
            this.selectedInstance = instance;
            this.showStopDialog = true;
        },

        restartInstance() {
            this.actionLoading = true;
            
            this.$inertia.post(route('instances.restart', this.selectedInstance.id), {}, {
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
            
            this.$inertia.post(route('instances.stop', this.selectedInstance.id), {}, {
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

        syncAll() {
            this.$inertia.post(route('fly-sync.run'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Sync job dispatched for all clients',
                    });
                },
            });
        },
    },
};
</script>