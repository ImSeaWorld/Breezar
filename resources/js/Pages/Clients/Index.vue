<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Clients</div>
                <q-btn
                    v-if="$page.props.auth.user.role === 'admin'"
                    color="primary"
                    icon="mdi-plus"
                    label="New Client"
                    @click="createClient"
                />
            </div>

            <q-card>
                <q-card-section>
                    <q-input
                        v-model="search"
                        label="Search clients..."
                        filled
                        clearable
                        @update:model-value="handleSearch"
                        class="q-mb-md"
                    >
                        <template v-slot:prepend>
                            <q-icon name="mdi-magnify" />
                        </template>
                    </q-input>

                    <q-table
                        :rows="clients.data"
                        :columns="columns"
                        row-key="id"
                        :pagination="pagination"
                        @request="onRequest"
                        flat
                        bordered
                    >
                        <template v-slot:body-cell-name="props">
                            <q-td :props="props">
                                <InertiaLink
                                    :href="route('clients.show', props.row.id)"
                                    class="text-primary text-weight-medium"
                                >
                                    {{ props.row.name }}
                                </InertiaLink>
                            </q-td>
                        </template>

                        <template v-slot:body-cell-status="props">
                            <q-td :props="props">
                                <q-chip
                                    :color="props.row.status === 'active' ? 'positive' : 'negative'"
                                    text-color="white"
                                    size="sm"
                                    dense
                                >
                                    {{ props.row.status }}
                                </q-chip>
                            </q-td>
                        </template>

                        <template v-slot:body-cell-instances="props">
                            <q-td :props="props">
                                <q-chip size="sm" dense>
                                    {{ props.row.instances_count }} instances
                                </q-chip>
                            </q-td>
                        </template>

                        <template v-slot:body-cell-reports="props">
                            <q-td :props="props">
                                <q-chip size="sm" dense>
                                    {{ props.row.reports_count }} reports
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
                                    @click="viewClient(props.row.id)"
                                />
                                <q-btn
                                    v-if="$page.props.auth.user.role === 'admin'"
                                    size="sm"
                                    flat
                                    dense
                                    icon="mdi-pencil"
                                    @click="editClient(props.row.id)"
                                />
                                <q-btn
                                    v-if="$page.props.auth.user.role === 'admin'"
                                    size="sm"
                                    flat
                                    dense
                                    icon="mdi-delete"
                                    color="negative"
                                    @click="confirmDelete(props.row)"
                                />
                            </q-td>
                        </template>
                    </q-table>
                </q-card-section>
            </q-card>

            <!-- Delete Dialog -->
            <q-dialog v-model="showDeleteDialog">
                <q-card>
                    <q-card-section class="row items-center">
                        <q-icon name="mdi-alert" color="negative" size="2rem" />
                        <span class="q-ml-sm">
                            Are you sure you want to delete client "{{ clientToDelete?.name }}"?
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
import { debounce } from 'quasar';

export default {
    components: {
        AuthenticatedLayout,
    },

    props: {
        clients: Object,
        filters: Object,
    },

    data() {
        return {
            search: this.filters.search || '',
            showDeleteDialog: false,
            clientToDelete: null,
            deleting: false,
            columns: [
                { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
                { name: 'fly_org_id', label: 'Fly Org ID', field: 'fly_org_id', align: 'left' },
                { name: 'status', label: 'Status', field: 'status', align: 'center' },
                { name: 'instances', label: 'Instances', align: 'center' },
                { name: 'reports', label: 'Reports', align: 'center' },
                { name: 'billing_start_date', label: 'Billing Start', field: 'billing_start_date', align: 'left' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
            pagination: {
                page: this.clients.current_page,
                rowsPerPage: this.clients.per_page,
                rowsNumber: this.clients.total,
            },
        };
    },

    methods: {
        handleSearch: debounce(function () {
            this.onRequest({
                pagination: {
                    page: 1,
                    rowsPerPage: this.pagination.rowsPerPage,
                },
            });
        }, 300),

        onRequest(props) {
            const { page, rowsPerPage } = props.pagination;
            
            this.$inertia.get(route('clients.index'), {
                page,
                search: this.search,
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.pagination.page = this.clients.current_page;
                    this.pagination.rowsPerPage = this.clients.per_page;
                    this.pagination.rowsNumber = this.clients.total;
                },
            });
        },

        createClient() {
            this.$inertia.visit(route('clients.create'));
        },

        viewClient(id) {
            this.$inertia.visit(route('clients.show', id));
        },

        editClient(id) {
            this.$inertia.visit(route('clients.edit', id));
        },

        confirmDelete(client) {
            this.clientToDelete = client;
            this.showDeleteDialog = true;
        },

        deleteClient() {
            this.deleting = true;
            
            this.$inertia.delete(route('clients.destroy', this.clientToDelete.id), {
                onSuccess: () => {
                    this.showDeleteDialog = false;
                    this.clientToDelete = null;
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
    },
};
</script>