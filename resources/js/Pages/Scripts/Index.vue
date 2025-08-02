<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Scripts</div>
                <q-btn
                    color="primary"
                    icon="mdi-plus"
                    label="Create Script"
                    @click="$inertia.visit(route('scripts.create'))"
                />
            </div>

            <!-- Search -->
            <q-card>
                <q-card-section>
                    <q-input
                        v-model="filters.search"
                        label="Search scripts"
                        filled
                        clearable
                        @update:model-value="debouncedSearch"
                    >
                        <template v-slot:prepend>
                            <q-icon name="mdi-magnify" />
                        </template>
                    </q-input>
                </q-card-section>
            </q-card>

            <!-- Scripts Table -->
            <q-card>
                <q-table
                    :rows="scripts.data"
                    :columns="columns"
                    row-key="id"
                    flat
                    :pagination="tablePagination"
                    @request="onRequest"
                >
                    <template v-slot:body-cell-name="props">
                        <q-td :props="props">
                            <div>
                                <div class="text-weight-medium">{{ props.row.name }}</div>
                                <div class="text-caption text-grey-7">{{ props.row.description }}</div>
                            </div>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-creator="props">
                        <q-td :props="props">
                            {{ props.row.creator?.name || 'Unknown' }}
                        </q-td>
                    </template>

                    <template v-slot:body-cell-executions_count="props">
                        <q-td :props="props">
                            <q-chip size="sm">
                                {{ props.row.executions_count }}
                            </q-chip>
                        </q-td>
                    </template>

                    <template v-slot:body-cell-last_run_at="props">
                        <q-td :props="props">
                            {{ props.row.last_run_at ? formatDate(props.row.last_run_at) : 'Never' }}
                        </q-td>
                    </template>

                    <template v-slot:body-cell-actions="props">
                        <q-td :props="props">
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-eye"
                                @click="viewScript(props.row.id)"
                            >
                                <q-tooltip>View</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-play"
                                color="positive"
                                @click="executeScript(props.row.id)"
                            >
                                <q-tooltip>Execute</q-tooltip>
                            </q-btn>
                            <q-btn
                                size="sm"
                                flat
                                dense
                                icon="mdi-pencil"
                                @click="editScript(props.row.id)"
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
                        <div class="text-h6">Delete Script</div>
                    </q-card-section>

                    <q-card-section>
                        Are you sure you want to delete script "{{ selectedScript?.name }}"?
                        This action cannot be undone.
                    </q-card-section>

                    <q-card-actions align="right">
                        <q-btn flat label="Cancel" v-close-popup />
                        <q-btn
                            flat
                            label="Delete"
                            color="negative"
                            @click="deleteScript"
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
        scripts: Object,
        filters: Object,
    },

    data() {
        return {
            showDeleteDialog: false,
            selectedScript: null,
            deleting: false,
            columns: [
                { name: 'name', label: 'Script', field: 'name', align: 'left' },
                { name: 'creator', label: 'Created By', align: 'left' },
                { name: 'executions_count', label: 'Executions', field: 'executions_count', align: 'center' },
                { name: 'last_run_at', label: 'Last Run', field: 'last_run_at', align: 'left' },
                { name: 'actions', label: 'Actions', align: 'center' },
            ],
        };
    },

    computed: {
        tablePagination() {
            return {
                page: this.scripts.current_page,
                rowsPerPage: this.scripts.per_page,
                rowsNumber: this.scripts.total,
            };
        },
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        applyFilters() {
            this.$inertia.get(route('scripts.index'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        debouncedSearch: debounce(function() {
            this.applyFilters();
        }, 500),

        onRequest(props) {
            const { page } = props.pagination;
            
            this.$inertia.get(route('scripts.index'), {
                ...this.filters,
                page,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        viewScript(id) {
            this.$inertia.visit(route('scripts.show', id));
        },

        editScript(id) {
            this.$inertia.visit(route('scripts.edit', id));
        },

        executeScript(id) {
            this.$inertia.visit(route('scripts.show', id));
        },

        confirmDelete(script) {
            this.selectedScript = script;
            this.showDeleteDialog = true;
        },

        deleteScript() {
            this.deleting = true;
            
            this.$inertia.delete(route('scripts.destroy', this.selectedScript.id), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Script deleted successfully',
                    });
                    this.showDeleteDialog = false;
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to delete script',
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