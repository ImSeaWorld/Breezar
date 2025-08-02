<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Users</div>
                <q-btn
                    color="primary"
                    icon="mdi-plus"
                    label="Add User"
                    @click="$inertia.visit(route('users.create'))"
                />
            </div>

            <!-- Search and Filters -->
            <q-card>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-6">
                            <q-input
                                v-model="filters.search"
                                label="Search users"
                                filled
                                clearable
                                @update:model-value="debouncedSearch"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="mdi-magnify" />
                                </template>
                            </q-input>
                        </div>

                        <div class="col-12 col-md-6">
                            <q-select
                                v-model="filters.role"
                                :options="roleOptions"
                                label="Filter by Role"
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

            <!-- Users Table -->
            <q-card>
                <q-table
                    :rows="users.data"
                    :columns="columns"
                    row-key="id"
                    flat
                    :pagination="tablePagination"
                    @request="onRequest"
                >
                    <template v-slot:body-cell-name="props">
                        <q-td :props="props">
                            <div class="flex items-center">
                                <q-avatar size="32px" color="primary" text-color="white">
                                    {{ props.row.name.charAt(0) }}
                                </q-avatar>
                                <div class="q-ml-sm">
                                    <div class="text-weight-medium">{{ props.row.name }}</div>
                                    <div class="text-caption text-grey-7">{{ props.row.email }}</div>
                                </div>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-role="props">
                        <q-td :props="props">
                            <q-chip
                                :color="props.row.role === 'admin' ? 'primary' : 'secondary'"
                                text-color="white"
                                size="sm"
                            >
                                {{ props.row.role }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-clients_count="props">
                        <q-td :props="props">
                            <span v-if="props.row.role === 'admin'" class="text-grey-6">All Clients</span>
                            <span v-else>{{ props.row.clients_count }}</span>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-two_factor="props">
                        <q-td :props="props">
                            <q-icon
                                :name="props.row.two_factor_secret ? 'mdi-shield-check' : 'mdi-shield-off'"
                                :color="props.row.two_factor_secret ? 'positive' : 'grey'"
                                size="20px"
                            >
                                <q-tooltip>
                                    {{ props.row.two_factor_secret ? '2FA Enabled' : '2FA Disabled' }}
                                </q-tooltip>
                            </q-icon>
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
                                @click="viewUser(props.row.id)"
                            >
                                <q-tooltip>View</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-pencil"
                                @click="editUser(props.row.id)"
                            >
                                <q-tooltip>Edit</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-delete"
                                color="negative"
                                @click="confirmDelete(props.row)"
                                :disable="props.row.id === $page.props.auth.user.id"
                            >
                                <q-tooltip>Delete</q-tooltip>
                            </q-btn>
                        </q-td>
                    </template>
                </q-table>
            </q-card>

            <!-- Delete Dialog -->
            <q-dialog v-model="showDeleteDialog">
                <q-card style="min-width: 300px">
                    <q-card-section>
                        <div class="text-h6">Delete User</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to delete user "{{ selectedUser?.name }}"?
                        This action cannot be undone.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Delete"
                            color="negative"
                            @click="deleteUser"
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
        users: Object,
        filters: Object,
    },

    data() {
        return {
            showDeleteDialog: false,
            selectedUser: null,
            deleting: false,
            columns: [
                { name: 'name', label: 'User', field: 'name', align: 'left' },
                { name: 'role', label: 'Role', field: 'role', align: 'center' },
                { name: 'clients_count', label: 'Clients', field: 'clients_count', align: 'center' },
                { name: 'two_factor', label: '2FA', align: 'center' },
                { name: 'created_at', label: 'Created', field: 'created_at', align: 'left' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
            roleOptions: [
                { label: 'Admin', value: 'admin' },
                { label: 'Manager', value: 'manager' },
            ],
        };
    },

    computed: {
        tablePagination() {
            return {
                page: this.users.current_page,
                rowsPerPage: this.users.per_page,
                rowsNumber: this.users.total,
            };
        },
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        applyFilters() {
            this.$inertia.get(route('users.index'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        debouncedSearch: debounce(function() {
            this.applyFilters();
        }, 500),

        onRequest(props) {
            const { page } = props.pagination;
            
            this.$inertia.get(route('users.index'), {
                ...this.filters,
                page,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        viewUser(id) {
            this.$inertia.visit(route('users.show', id));
        },

        editUser(id) {
            this.$inertia.visit(route('users.edit', id));
        },

        confirmDelete(user) {
            this.selectedUser = user;
            this.showDeleteDialog = true;
        },

        deleteUser() {
            this.deleting = true;
            
            this.$inertia.delete(route('users.destroy', this.selectedUser.id), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'User deleted successfully',
                    });
                    this.showDeleteDialog = false;
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to delete user',
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