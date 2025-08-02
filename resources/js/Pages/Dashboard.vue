<template>
    <AuthenticatedLayout>
        <div>
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Dashboard</div>
            </div>

            <!-- Stats Cards -->
            <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6 col-md-3">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.total_clients }}</div>
                            <div class="text-subtitle2 text-grey-7">Total Clients</div>
                        </q-card-section>
                        <q-icon name="mdi-domain" size="3rem" class="stat-icon text-primary" />
                    </q-card>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.active_clients }}</div>
                            <div class="text-subtitle2 text-grey-7">Active Clients</div>
                        </q-card-section>
                        <q-icon name="mdi-check-circle" size="3rem" class="stat-icon text-positive" />
                    </q-card>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.total_instances }}</div>
                            <div class="text-subtitle2 text-grey-7">Total Instances</div>
                        </q-card-section>
                        <q-icon name="mdi-server" size="3rem" class="stat-icon text-info" />
                    </q-card>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.running_instances }}</div>
                            <div class="text-subtitle2 text-grey-7">Running Instances</div>
                        </q-card-section>
                        <q-icon name="mdi-play-circle" size="3rem" class="stat-icon text-accent" />
                    </q-card>
                </div>

                <div class="col-12 col-sm-6 col-md-3" v-if="stats.total_users">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.total_users }}</div>
                            <div class="text-subtitle2 text-grey-7">Total Users</div>
                        </q-card-section>
                        <q-icon name="mdi-account-multiple" size="3rem" class="stat-icon text-secondary" />
                    </q-card>
                </div>

                <div class="col-12 col-sm-6 col-md-3" v-if="stats.admin_users">
                    <q-card flat square bordered class="stat-card">
                        <q-card-section>
                            <div class="text-h6">{{ stats.admin_users }}</div>
                            <div class="text-subtitle2 text-grey-7">Admin Users</div>
                        </q-card-section>
                        <q-icon name="mdi-shield-account" size="3rem" class="stat-icon text-warning" />
                    </q-card>
                </div>
            </div>

            <!-- Client Overview -->
            <div class="row q-col-gutter-md q-mt-md">
                <div class="col-12 col-lg-8">
                    <q-card flat square bordered>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Client Overview</div>
                            <q-table
                                :rows="clients"
                                :columns="clientColumns"
                                row-key="id"
                                :pagination="{ rowsPerPage: 10 }"
                                flat
                                bordered
                            >
                                <template v-slot:body-cell-instances="props">
                                    <q-td :props="props">
                                        <div class="q-gutter-xs">
                                            <q-chip 
                                                v-for="instance in props.row.instances" 
                                                :key="instance.id"
                                                :color="instance.status === 'running' ? 'positive' : 'negative'"
                                                text-color="white"
                                                size="sm"
                                                dense
                                            >
                                                {{ instance.type.toUpperCase() }}: {{ instance.status }}
                                            </q-chip>
                                        </div>
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
                                    </q-td>
                                </template>
                            </q-table>
                        </q-card-section>
                    </q-card>
                </div>

                <div class="col-12 col-lg-4">
                    <!-- Recent Logins -->
                    <q-card flat square bordered class="q-mb-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Logins</div>
                            <q-list>
                                <q-item v-for="login in recentLogins" :key="login.id">
                                    <q-item-section avatar>
                                        <q-avatar color="primary" text-color="white" size="sm">
                                            {{ login.user.name.charAt(0) }}
                                        </q-avatar>
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label>{{ login.user.name }}</q-item-label>
                                        <q-item-label caption>
                                            {{ formatDateTime(login.timestamp) }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>

                    <!-- Recent Activities -->
                    <q-card flat square bordered>
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
        </div>

        <!-- Refresh Button -->
        <q-page-sticky position="bottom-right" :offset="[18, 18]">
            <q-btn
                fab
                icon="mdi-refresh"
                color="primary"
                @click="refreshDashboard"
                :loading="refreshing"
            />
        </q-page-sticky>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useQuasar } from 'quasar';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';

const props = defineProps({
    stats: Object,
    clients: Array,
    recentActivities: Array,
    recentLogins: Array,
});

const $q = useQuasar();
const refreshing = ref(false);

const clientColumns = [
    { name: 'name', label: 'Client Name', field: 'name', align: 'left' },
    { name: 'fly_org_id', label: 'Fly Org ID', field: 'fly_org_id', align: 'left' },
    { name: 'instances', label: 'Instances', field: 'instances', align: 'left' },
    { name: 'actions', label: 'Actions', align: 'center' },
];

const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleString();
};

const formatActivity = (activity) => {
    const actions = {
        '2fa_enabled': 'enabled 2FA',
        '2fa_disabled': 'disabled 2FA',
        'client_created': 'created client',
        'client_updated': 'updated client',
        'instance_restarted': 'restarted instance',
        'script_executed': 'executed script',
    };
    
    return `${activity.user.name} ${actions[activity.action] || activity.action}`;
};

const getActivityIcon = (action) => {
    const icons = {
        '2fa_enabled': 'mdi-shield-check',
        '2fa_disabled': 'mdi-shield-off',
        'client_created': 'mdi-plus-circle',
        'client_updated': 'mdi-pencil',
        'instance_restarted': 'mdi-restart',
        'script_executed': 'mdi-play',
    };
    
    return icons[action] || 'mdi-information';
};

const viewClient = (clientId) => {
    router.visit(route('clients.show', clientId));
};

const refreshDashboard = async () => {
    refreshing.value = true;
    
    try {
        await router.post(route('dashboard.refresh'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                $q.notify({
                    type: 'positive',
                    message: 'Dashboard refreshed',
                });
            },
        });
    } finally {
        refreshing.value = false;
    }
};
</script>

<style scoped>
.stat-card {
    position: relative;
    overflow: hidden;
}

.stat-icon {
    position: absolute;
    bottom: -10px;
    right: -10px;
    opacity: 0.2;
}
</style>