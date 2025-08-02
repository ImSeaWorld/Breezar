<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <q-card>
                <q-card-section>
                    <div class="text-h6 q-mb-md">Fly.io Sync Management</div>
                    <p class="text-body2 text-grey-8">
                        Synchronize Fly.io instances with the local database. The sync runs automatically every 30 minutes.
                    </p>
                </q-card-section>

                <q-separator />

                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-6">
                            <q-select
                                v-model="selectedClient"
                                :options="clientOptions"
                                label="Select Client (optional)"
                                clearable
                                filled
                                emit-value
                                map-options
                                option-value="value"
                                option-label="label"
                            >
                                <template v-slot:option="scope">
                                    <q-item v-bind="scope.itemProps">
                                        <q-item-section>
                                            <q-item-label>{{ scope.opt.label }}</q-item-label>
                                            <q-item-label caption>
                                                {{ scope.opt.instances_count }} instances
                                            </q-item-label>
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
                        </div>
                        <div class="col-12 col-md-6">
                            <q-btn
                                color="primary"
                                label="Run Sync Now"
                                icon="mdi-sync"
                                @click="runSync"
                                :loading="syncing"
                                class="full-width"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <q-card>
                <q-card-section>
                    <div class="text-h6 q-mb-md">Sync History</div>
                    <q-table
                        :rows="syncHistory"
                        :columns="columns"
                        row-key="id"
                        :pagination="{ rowsPerPage: 15 }"
                        flat
                        bordered
                    >
                        <template v-slot:body-cell-action="props">
                            <q-td :props="props">
                                <q-chip
                                    :color="props.row.action === 'fly_sync_completed' ? 'positive' : 'negative'"
                                    text-color="white"
                                    size="sm"
                                    dense
                                >
                                    {{ props.row.action === 'fly_sync_completed' ? 'Completed' : 'Failed' }}
                                </q-chip>
                            </q-td>
                        </template>
                        <template v-slot:body-cell-metadata="props">
                            <q-td :props="props">
                                <div v-if="props.row.metadata">
                                    <div v-if="props.row.metadata.synced_count">
                                        Synced: {{ props.row.metadata.synced_count }} instances
                                    </div>
                                    <div v-if="props.row.metadata.error" class="text-negative">
                                        Error: {{ props.row.metadata.error }}
                                    </div>
                                    <div v-if="props.row.metadata.initiated_by">
                                        Initiated by: {{ props.row.metadata.initiated_by }}
                                    </div>
                                </div>
                            </q-td>
                        </template>
                        <template v-slot:body-cell-created_at="props">
                            <q-td :props="props">
                                {{ formatDateTime(props.row.created_at) }}
                            </q-td>
                        </template>
                    </q-table>
                </q-card-section>
            </q-card>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useQuasar } from 'quasar';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({
    syncHistory: Array,
    clients: Array,
});

const $q = useQuasar();
const selectedClient = ref(null);
const syncing = ref(false);

const columns = [
    { name: 'action', label: 'Status', field: 'action', align: 'left' },
    { name: 'client', label: 'Client', field: 'client', align: 'left' },
    { name: 'user', label: 'User', field: 'user', align: 'left' },
    { name: 'metadata', label: 'Details', field: 'metadata', align: 'left' },
    { name: 'created_at', label: 'Date/Time', field: 'created_at', align: 'left' },
];

const clientOptions = computed(() => {
    return [
        { label: 'All Active Clients', value: null, instances_count: 'all' },
        ...props.clients.map(client => ({
            label: `${client.name} (${client.fly_org_id})`,
            value: client.id,
            instances_count: client.instances_count,
        }))
    ];
});

const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleString();
};

const runSync = async () => {
    syncing.value = true;
    
    try {
        await router.post(route('fly-sync.run'), {
            client_id: selectedClient.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                $q.notify({
                    type: 'positive',
                    message: 'Sync job has been dispatched',
                });
            },
            onError: () => {
                $q.notify({
                    type: 'negative',
                    message: 'Failed to dispatch sync job',
                });
            },
        });
    } finally {
        syncing.value = false;
    }
};
</script>