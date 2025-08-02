<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Activity Logs</div>
                <q-btn
                    color="primary"
                    icon="mdi-refresh"
                    label="Refresh"
                    @click="refreshActivities"
                />
            </div>

            <!-- Filters -->
            <q-card>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-3">
                            <q-select
                                v-model="filters.user_id"
                                :options="userOptions"
                                label="Filter by User"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-3">
                            <q-select
                                v-model="filters.action"
                                :options="actionOptions"
                                label="Filter by Action"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-3">
                            <q-select
                                v-model="filters.resource_type"
                                :options="resourceTypeOptions"
                                label="Filter by Resource Type"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-3">
                            <q-input
                                v-model="filters.search"
                                label="Search"
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
                            <q-input
                                v-model="filters.date_from"
                                label="Date From"
                                filled
                                type="date"
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-3">
                            <q-input
                                v-model="filters.date_to"
                                label="Date To"
                                filled
                                type="date"
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <!-- Activities Table -->
            <q-card>
                <q-table
                    :rows="activities.data"
                    :columns="columns"
                    row-key="id"
                    flat
                    :pagination="tablePagination"
                    @request="onRequest"
                >
                    <template v-slot:body-cell-user="props">
                        <q-td :props="props">
                            <div v-if="props.row.user" class="flex items-center">
                                <q-avatar size="24px" color="primary" text-color="white">
                                    {{ props.row.user.name.charAt(0) }}
                                </q-avatar>
                                <span class="q-ml-sm">{{ props.row.user.name }}</span>
                            </div>
                            <span v-else class="text-grey-6">System</span>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-action="props">
                        <q-td :props="props">
                            <q-chip
                                size="sm"
                                :color="getActionColor(props.row.action)"
                                text-color="white"
                            >
                                {{ formatAction(props.row.action) }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-resource="props">
                        <q-td :props="props">
                            <div>
                                <div class="text-weight-medium">{{ props.row.resource_type }}</div>
                                <div class="text-caption text-grey-7">ID: {{ props.row.resource_id }}</div>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-details="props">
                        <q-td :props="props">
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-information"
                                @click="showDetails(props.row)"
                            >
                                <q-tooltip>View Details</q-tooltip>
                            </q-btn>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-created_at="props">
                        <q-td :props="props">
                            {{ formatDateTime(props.row.created_at) }}
                        </q-td>
                    </template>
                </q-table>
            </q-card>

            <!-- Details Dialog -->
            <q-dialog v-model="showDetailsDialog" maximized>
                <q-card>
                    <q-card-section class="row items-center">
                        <div class="text-h6">Activity Details</div>
                        <q-space />
                        <q-btn icon="mdi-close" flat round dense v-close-popup />
                    </q-card-section>

                    <q-separator />

                    <q-card-section v-if="selectedActivity">
                        <div class="row q-col-gutter-md">
                            <div class="col-12 col-md-6">
                                <q-list bordered separator>
                                    <q-item>
                                        <q-item-section>
                                            <q-item-label caption>User</q-item-label>
                                            <q-item-label>{{ selectedActivity.user?.name || 'System' }}</q-item-label>
                                        </q-item-section>
                                    </q-item>

                                    <q-item>
                                        <q-item-section>
                                            <q-item-label caption>Action</q-item-label>
                                            <q-item-label>{{ formatAction(selectedActivity.action) }}</q-item-label>
                                        </q-item-section>
                                    </q-item>

                                    <q-item>
                                        <q-item-section>
                                            <q-item-label caption>Resource</q-item-label>
                                            <q-item-label>
                                                {{ selectedActivity.resource_type }} (ID: {{ selectedActivity.resource_id }})
                                            </q-item-label>
                                        </q-item-section>
                                    </q-item>

                                    <q-item>
                                        <q-item-section>
                                            <q-item-label caption>Date/Time</q-item-label>
                                            <q-item-label>{{ formatDateTime(selectedActivity.created_at) }}</q-item-label>
                                        </q-item-section>
                                    </q-item>
                                </q-list>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="text-subtitle2 q-mb-sm">Metadata</div>
                                <q-card flat bordered>
                                    <q-card-section>
                                        <pre class="q-ma-none">{{ JSON.stringify(selectedActivity.metadata, null, 2) }}</pre>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>
                    </q-card-section>
                </q-card>
            </q-dialog>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { debounce } from 'quasar';

export default {
    components: {
        AuthenticatedLayout,
    },

    props: {
        activities: Object,
        actions: Array,
        resourceTypes: Array,
        users: Array,
        filters: Object,
    },

    data() {
        return {
            showDetailsDialog: false,
            selectedActivity: null,
            columns: [
                { name: 'user', label: 'User', align: 'left' },
                { name: 'action', label: 'Action', field: 'action', align: 'left' },
                { name: 'resource', label: 'Resource', align: 'left' },
                { name: 'created_at', label: 'Date/Time', field: 'created_at', align: 'left' },
                { name: 'details', label: '', align: 'center' },
            ],
        };
    },

    computed: {
        tablePagination() {
            return {
                page: this.activities.current_page,
                rowsPerPage: this.activities.per_page,
                rowsNumber: this.activities.total,
            };
        },

        userOptions() {
            return this.users.map(user => ({
                label: user.name,
                value: user.id,
            }));
        },

        actionOptions() {
            const actions = Array.isArray(this.actions) ? this.actions : [];
            return actions.map(action => ({
                label: this.formatAction(action),
                value: action,
            }));
        },

        resourceTypeOptions() {
            return this.resourceTypes
                .filter(type => type != null)
                .map(type => ({
                    label: this.formatResourceType(type),
                    value: type,
                }));
        },
    },

    methods: {
        formatDateTime(date) {
            return new Date(date).toLocaleString();
        },

        formatAction(action) {
            const formatted = action.replace(/_/g, ' ')
                                  .replace(/\b\w/g, l => l.toUpperCase());
            return formatted;
        },

        formatResourceType(type) {
            if (!type) return 'Unknown';
            return type.charAt(0).toUpperCase() + type.slice(1);
        },

        getActionColor(action) {
            if (action.includes('created')) return 'positive';
            if (action.includes('updated')) return 'primary';
            if (action.includes('deleted')) return 'negative';
            if (action.includes('failed')) return 'negative';
            if (action.includes('executed')) return 'secondary';
            if (action.includes('viewed')) return 'info';
            return 'grey';
        },

        applyFilters() {
            this.$inertia.get(route('activity.index'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        debouncedSearch: debounce(function() {
            this.applyFilters();
        }, 500),

        onRequest(props) {
            const { page } = props.pagination;
            
            this.$inertia.get(route('activity.index'), {
                ...this.filters,
                page,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        refreshActivities() {
            this.$inertia.reload({
                preserveState: true,
                preserveScroll: true,
            });
        },

        showDetails(activity) {
            this.selectedActivity = activity;
            this.showDetailsDialog = true;
        },
    },
};
</script>

<style scoped>
pre {
    font-size: 12px;
    line-height: 1.4;
    overflow-x: auto;
}
</style>