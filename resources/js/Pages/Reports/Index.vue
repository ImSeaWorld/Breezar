<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Reports</div>
                <q-btn
                    color="primary"
                    icon="mdi-plus"
                    label="Generate Report"
                    @click="showGenerateDialog = true"
                />
            </div>

            <!-- Filters -->
            <q-card>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-4">
                            <q-select
                                v-model="filters.client_id"
                                :options="clientOptions"
                                label="Filter by Client"
                                filled
                                emit-value
                                map-options
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <div class="col-12 col-md-4">
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

                        <div class="col-12 col-md-4">
                            <q-input
                                v-model="filters.month"
                                label="Filter by Month"
                                filled
                                type="month"
                                clearable
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <!-- Reports Table -->
            <q-card>
                <q-table
                    :rows="reports.data"
                    :columns="columns"
                    row-key="id"
                    flat
                    :pagination="tablePagination"
                    @request="onRequest"
                >
                    <template v-slot:body-cell-client="props">
                        <q-td :props="props">
                            <Link 
                                :href="route('clients.show', props.row.client.id)"
                                class="text-primary text-weight-medium"
                            >
                                {{ props.row.client.name }}
                            </Link>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-type="props">
                        <q-td :props="props">
                            <q-chip size="sm" :color="getTypeColor(props.row.type)">
                                {{ formatType(props.row.type) }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-report_month="props">
                        <q-td :props="props">
                            {{ formatMonth(props.row.report_month) }}
                        </q-td>
                    </template>

                    <template v-slot:body-cell-generated_at="props">
                        <q-td :props="props">
                            {{ formatDate(props.row.generated_at) }}
                        </q-td>
                    </template>

                    <template v-slot:body-cell-actions="props">
                        <q-td :props="props">
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-eye"
                                @click="viewReport(props.row.id)"
                            >
                                <q-tooltip>View Report</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-download"
                                @click="downloadReport(props.row.id)"
                            >
                                <q-tooltip>Download Report</q-tooltip>
                            </q-btn>
                        </q-td>
                    </template>
                </q-table>
            </q-card>

            <!-- Generate Report Dialog -->
            <q-dialog v-model="showGenerateDialog">
                <q-card style="min-width: 400px">
                    <q-form @submit="generateReport">
                        <q-card-section>
                            <div class="text-h6">Generate New Report</div>
                        </q-card-section>

                        <q-card-section>
                            <div class="q-gutter-md">
                                <q-select
                                    v-model="generateForm.client_id"
                                    :options="clientOptions"
                                    label="Select Client"
                                    filled
                                    emit-value
                                    map-options
                                    :rules="[val => !!val || 'Client is required']"
                                />

                                <q-select
                                    v-model="generateForm.type"
                                    :options="typeOptions"
                                    label="Report Type"
                                    filled
                                    emit-value
                                    map-options
                                    :rules="[val => !!val || 'Report type is required']"
                                />

                                <q-input
                                    v-model="generateForm.month"
                                    label="Report Month"
                                    filled
                                    type="month"
                                    :rules="[val => !!val || 'Month is required']"
                                />
                            </div>
                        </q-card-section>

                        <q-card-actions align="right">
                            <q-btn flat label="Cancel" v-close-popup />
                            <q-btn
                                type="submit"
                                color="primary"
                                label="Generate"
                                :loading="generating"
                            />
                        </q-card-actions>
                    </q-form>
                </q-card>
            </q-dialog>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { useForm, Link } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Link,
    },

    props: {
        reports: Object,
        clients: Array,
        filters: Object,
    },

    data() {
        return {
            showGenerateDialog: false,
            generating: false,
            generateForm: useForm({
                client_id: null,
                type: null,
                month: new Date().toISOString().slice(0, 7),
            }),
            columns: [
                { name: 'client', label: 'Client', field: 'client', align: 'left' },
                { name: 'type', label: 'Type', field: 'type', align: 'center' },
                { name: 'report_month', label: 'Month', field: 'report_month', align: 'left' },
                { name: 'generated_at', label: 'Generated', field: 'generated_at', align: 'left' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
            typeOptions: [
                { label: 'Usage Report', value: 'usage' },
                { label: 'Performance Report', value: 'performance' },
                { label: 'Work Items Report', value: 'work_items' },
                { label: 'Custom Report', value: 'custom' },
            ],
        };
    },

    computed: {
        clientOptions() {
            return this.clients.map(client => ({
                label: client.name,
                value: client.id,
            }));
        },

        tablePagination() {
            return {
                page: this.reports.current_page,
                rowsPerPage: this.reports.per_page,
                rowsNumber: this.reports.total,
            };
        },
    },

    methods: {
        formatType(type) {
            const types = {
                'usage': 'Usage',
                'performance': 'Performance',
                'work_items': 'Work Items',
                'custom': 'Custom',
            };
            
            return types[type] || type;
        },

        getTypeColor(type) {
            const colors = {
                'usage': 'primary',
                'performance': 'secondary',
                'work_items': 'accent',
                'custom': 'info',
            };
            
            return colors[type] || 'grey';
        },

        formatMonth(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        },

        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        applyFilters() {
            this.$inertia.get(route('reports.index'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        onRequest(props) {
            const { page } = props.pagination;
            
            this.$inertia.get(route('reports.index'), {
                ...this.filters,
                page,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        viewReport(id) {
            this.$inertia.visit(route('reports.show', id));
        },

        downloadReport(id) {
            // Implement download functionality
            window.open(route('reports.download', id), '_blank');
        },

        generateReport() {
            this.generating = true;
            
            this.generateForm.post(route('reports.generate'), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Report generation started',
                    });
                    this.showGenerateDialog = false;
                    this.generateForm.reset();
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to generate report',
                    });
                },
                onFinish: () => {
                    this.generating = false;
                },
            });
        },
    },
};
</script>