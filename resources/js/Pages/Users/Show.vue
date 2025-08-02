<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">{{ user.name }}</div>
                    <div class="text-caption text-grey-7">{{ user.email }}</div>
                </div>
                <div>
                    <q-btn
                        color="primary"
                        icon="mdi-pencil"
                        label="Edit"
                        @click="$inertia.visit(route('users.edit', user.id))"
                        class="q-mr-sm"
                    />
                    <q-btn
                        color="secondary"
                        icon="mdi-arrow-left"
                        label="Back"
                        @click="$inertia.visit(route('users.index'))"
                    />
                </div>
            </div>

            <div class="row q-col-gutter-md">
                <!-- User Details -->
                <div class="col-12 col-md-4">
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">User Details</div>
                            
                            <q-list>
                                <q-item>
                                    <q-item-section avatar>
                                        <q-avatar size="48px" color="primary" text-color="white">
                                            {{ user.name.charAt(0) }}
                                        </q-avatar>
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label>{{ user.name }}</q-item-label>
                                        <q-item-label caption>{{ user.email }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-separator spaced />

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Role</q-item-label>
                                        <q-item-label>
                                            <q-chip
                                                :color="user.role === 'admin' ? 'primary' : 'secondary'"
                                                text-color="white"
                                                size="sm"
                                            >
                                                {{ user.role }}
                                            </q-chip>
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Two-Factor Authentication</q-item-label>
                                        <q-item-label>
                                            <q-icon
                                                :name="user.two_factor_secret ? 'mdi-shield-check' : 'mdi-shield-off'"
                                                :color="user.two_factor_secret ? 'positive' : 'grey'"
                                                size="20px"
                                            />
                                            <span class="q-ml-sm">
                                                {{ user.two_factor_secret ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Created</q-item-label>
                                        <q-item-label>{{ formatDate(user.created_at) }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Last Updated</q-item-label>
                                        <q-item-label>{{ formatDate(user.updated_at) }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>

                    <!-- Assigned Clients -->
                    <q-card class="q-mt-md" v-if="user.role === 'manager'">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Assigned Clients</div>
                            <q-list>
                                <q-item v-for="client in user.clients" :key="client.id">
                                    <q-item-section>
                                        <q-item-label>
                                            <Link 
                                                :href="route('clients.show', client.id)"
                                                class="text-primary"
                                            >
                                                {{ client.name }}
                                            </Link>
                                        </q-item-label>
                                        <q-item-label caption>{{ client.fly_org_id }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                                <q-item v-if="user.clients.length === 0">
                                    <q-item-section>
                                        <q-item-label class="text-grey-6">No clients assigned</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>
                </div>

                <!-- Activity and Login History -->
                <div class="col-12 col-md-8">
                    <!-- Recent Login History -->
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Login History</div>
                            
                            <q-table
                                :rows="user.login_logs"
                                :columns="loginColumns"
                                row-key="id"
                                flat
                                bordered
                                :pagination="{ rowsPerPage: 10 }"
                            >
                                <template v-slot:body-cell-success="props">
                                    <q-td :props="props">
                                        <q-icon
                                            :name="props.row.success ? 'mdi-check-circle' : 'mdi-close-circle'"
                                            :color="props.row.success ? 'positive' : 'negative'"
                                            size="20px"
                                        />
                                    </q-td>
                                </template>

                                <template v-slot:body-cell-timestamp="props">
                                    <q-td :props="props">
                                        {{ formatDateTime(props.row.timestamp) }}
                                    </q-td>
                                </template>
                            </q-table>
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
                                <q-item v-if="recentActivities.length === 0">
                                    <q-item-section>
                                        <q-item-label class="text-grey-6">No recent activities</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Link } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Link,
    },

    props: {
        user: Object,
        recentActivities: Array,
    },

    data() {
        return {
            loginColumns: [
                { name: 'success', label: 'Status', align: 'center' },
                { name: 'ip', label: 'IP Address', field: 'ip', align: 'left' },
                { name: 'user_agent', label: 'Browser', field: 'user_agent', align: 'left', format: val => this.formatUserAgent(val) },
                { name: 'timestamp', label: 'Time', field: 'timestamp', align: 'left' },
            ],
        };
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        formatDateTime(date) {
            return new Date(date).toLocaleString();
        },

        formatUserAgent(userAgent) {
            // Simple browser detection from user agent
            if (userAgent.includes('Chrome')) return 'Chrome';
            if (userAgent.includes('Firefox')) return 'Firefox';
            if (userAgent.includes('Safari')) return 'Safari';
            if (userAgent.includes('Edge')) return 'Edge';
            return 'Unknown';
        },

        formatActivity(activity) {
            const actions = {
                'client_created': `created client "${activity.metadata?.client_name || 'Unknown'}"`,
                'client_updated': `updated client "${activity.metadata?.client_name || 'Unknown'}"`,
                'user_created': `created user "${activity.metadata?.user_name || 'Unknown'}"`,
                'user_updated': `updated user "${activity.metadata?.user_name || 'Unknown'}"`,
                'instance_restarted': `restarted instance "${activity.metadata?.instance_name || 'Unknown'}"`,
                'report_generated': `generated ${activity.metadata?.report_type || 'Unknown'} report`,
            };
            
            return actions[activity.action] || activity.action;
        },

        getActivityIcon(action) {
            const icons = {
                'client_created': 'mdi-plus-circle',
                'client_updated': 'mdi-pencil',
                'user_created': 'mdi-account-plus',
                'user_updated': 'mdi-account-edit',
                'instance_restarted': 'mdi-restart',
                'report_generated': 'mdi-file-chart',
            };
            
            return icons[action] || 'mdi-information';
        },
    },
};
</script>