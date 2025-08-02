<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="text-h5 q-mb-md">Settings</div>

            <q-card>
                <q-tabs v-model="tab" align="left" active-color="primary">
                    <q-tab name="fly_api" icon="mdi-cloud" label="Fly.io API" />
                    <q-tab name="general" icon="mdi-cog" label="General" v-if="hasGeneralSettings" />
                </q-tabs>

                <q-separator />

                <q-tab-panels v-model="tab" animated>
                    <q-tab-panel name="fly_api">
                        <div class="text-h6 q-mb-md">Fly.io API Configuration</div>
                        
                        <form @submit.prevent="saveSettings">
                            <div class="q-gutter-md">
                                <q-input
                                    v-model="form.fly_api_token"
                                    label="API Token"
                                    filled
                                    :type="showToken ? 'text' : 'password'"
                                    hint="Your Fly.io Personal Access Token"
                                    :error="!!errors.fly_api_token"
                                    :error-message="errors.fly_api_token"
                                >
                                    <template v-slot:append>
                                        <q-icon
                                            :name="showToken ? 'mdi-eye-off' : 'mdi-eye'"
                                            class="cursor-pointer"
                                            @click="showToken = !showToken"
                                        />
                                    </template>
                                </q-input>

                                <q-input
                                    v-model="form.fly_org_id"
                                    label="Organization ID"
                                    filled
                                    hint="Default Fly.io Organization ID"
                                    :error="!!errors.fly_org_id"
                                    :error-message="errors.fly_org_id"
                                />

                                <q-input
                                    v-model="form.fly_api_endpoint"
                                    label="API Endpoint"
                                    filled
                                    hint="Fly.io GraphQL API Endpoint (usually https://api.fly.io/graphql)"
                                    :error="!!errors.fly_api_endpoint"
                                    :error-message="errors.fly_api_endpoint"
                                />

                                <div class="row q-gutter-sm">
                                    <q-btn
                                        type="button"
                                        color="secondary"
                                        label="Test Connection"
                                        icon="mdi-connection"
                                        @click="testConnection"
                                        :loading="testing"
                                        :disable="!form.fly_api_token || !form.fly_org_id"
                                    />
                                    
                                    <q-btn
                                        type="submit"
                                        color="primary"
                                        label="Save Settings"
                                        icon="mdi-content-save"
                                        :loading="saving"
                                    />
                                </div>

                                <q-banner
                                    v-if="testResult"
                                    :class="testResult.success ? 'bg-positive text-white' : 'bg-negative text-white'"
                                    class="q-mt-md"
                                >
                                    <template v-slot:avatar>
                                        <q-icon :name="testResult.success ? 'mdi-check-circle' : 'mdi-alert-circle'" />
                                    </template>
                                    {{ testResult.message }}
                                    <div v-if="testResult.organizations && testResult.organizations.length > 0" class="q-mt-sm">
                                        <div class="text-caption">Available Organizations:</div>
                                        <q-chip
                                            v-for="org in testResult.organizations"
                                            :key="org.id"
                                            color="white"
                                            text-color="black"
                                            size="sm"
                                        >
                                            {{ org.name }} ({{ org.id }})
                                        </q-chip>
                                    </div>
                                </q-banner>

                                <q-card flat bordered class="q-mt-lg">
                                    <q-card-section>
                                        <div class="text-subtitle1 text-weight-medium">
                                            <q-icon name="mdi-information-outline" class="q-mr-sm" />
                                            How to get your Fly.io API Token
                                        </div>
                                    </q-card-section>
                                    <q-card-section class="q-pt-none">
                                        <ol class="q-pl-md">
                                            <li>Visit <a href="https://fly.io/user/personal_access_tokens" target="_blank" class="text-primary">Fly.io Dashboard → Account → Settings → Access Tokens</a></li>
                                            <li>Click "Create Access Token" (NOT the CLI token)</li>
                                            <li>Give it a descriptive name (e.g., "LMS Manager")</li>
                                            <li>Copy the generated token and paste it above</li>
                                        </ol>
                                        <div class="text-warning q-mt-sm">
                                            <q-icon name="mdi-alert" />
                                            Keep your API token secure. It has full access to your Fly.io account.
                                        </div>
                                        <div class="text-info q-mt-sm">
                                            <q-icon name="mdi-information" />
                                            Note: Fly.io's GraphQL API is undocumented and may change. Consider using their REST Machines API for more stability.
                                        </div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </form>
                    </q-tab-panel>

                    <q-tab-panel name="general" v-if="hasGeneralSettings">
                        <div class="text-h6 q-mb-md">General Settings</div>
                        <div class="text-grey-7">
                            No general settings available yet.
                        </div>
                    </q-tab-panel>
                </q-tab-panels>
            </q-card>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { useForm } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
    },

    props: {
        settings: Object,
    },

    data() {
        return {
            tab: 'fly_api',
            showToken: false,
            testing: false,
            saving: false,
            testResult: null,
            form: useForm({
                fly_api_token: '',
                fly_org_id: '',
                fly_api_endpoint: '',
            }),
            errors: {},
        };
    },

    computed: {
        hasGeneralSettings() {
            return this.settings.general && Object.keys(this.settings.general).length > 0;
        },
    },

    mounted() {
        // Load existing settings
        if (this.settings.fly_api) {
            this.form.fly_api_token = this.settings.fly_api.fly_api_token?.value || '';
            this.form.fly_org_id = this.settings.fly_api.fly_org_id?.value || '';
            this.form.fly_api_endpoint = this.settings.fly_api.fly_api_endpoint?.value || 'https://api.fly.io/graphql';
        }
    },

    methods: {
        async testConnection() {
            this.testing = true;
            this.testResult = null;
            this.errors = {};

            try {
                const response = await axios.post(route('settings.test-connection'), {
                    api_token: this.form.fly_api_token,
                    org_id: this.form.fly_org_id,
                });

                this.testResult = response.data;
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                }
                this.testResult = {
                    success: false,
                    message: error.response?.data?.message || 'Connection test failed',
                };
            } finally {
                this.testing = false;
            }
        },

        saveSettings() {
            this.saving = true;
            this.errors = {};
            this.testResult = null;

            const settingsData = [
                {
                    key: 'fly_api_token',
                    value: this.form.fly_api_token,
                    type: 'encrypted',
                },
                {
                    key: 'fly_org_id',
                    value: this.form.fly_org_id,
                    type: 'string',
                },
                {
                    key: 'fly_api_endpoint',
                    value: this.form.fly_api_endpoint,
                    type: 'string',
                },
            ];

            this.$inertia.post(route('settings.update'), {
                settings: settingsData,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Settings saved successfully',
                    });
                },
                onError: (errors) => {
                    this.errors = errors;
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to save settings',
                    });
                },
                onFinish: () => {
                    this.saving = false;
                },
            });
        },
    },
};
</script>