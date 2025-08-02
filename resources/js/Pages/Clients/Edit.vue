<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Edit Client: {{ client.name }}</div>
            </div>

            <q-card>
                <q-form @submit="submit">
                    <q-card-section>
                        <div class="row q-col-gutter-md">
                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.name"
                                    label="Client Name"
                                    filled
                                    :error="!!form.errors.name"
                                    :error-message="form.errors.name"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.fly_org_id"
                                    label="Fly.io Organization ID"
                                    filled
                                    :error="!!form.errors.fly_org_id"
                                    :error-message="form.errors.fly_org_id"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-select
                                    v-model="form.status"
                                    :options="statusOptions"
                                    label="Status"
                                    filled
                                    emit-value
                                    map-options
                                    :error="!!form.errors.status"
                                    :error-message="form.errors.status"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.billing_start_date"
                                    label="Billing Start Date"
                                    filled
                                    type="date"
                                    :error="!!form.errors.billing_start_date"
                                    :error-message="form.errors.billing_start_date"
                                />
                            </div>

                            <div class="col-12">
                                <q-separator spaced />
                                <div class="text-h6 q-mb-md">Fly.io API Configuration</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.fly_api_token"
                                    label="Organization API Token (Optional)"
                                    filled
                                    :type="showToken ? 'text' : 'password'"
                                    hint="Leave empty to use global settings"
                                    :error="!!form.errors.fly_api_token"
                                    :error-message="form.errors.fly_api_token"
                                >
                                    <template v-slot:append>
                                        <q-icon
                                            :name="showToken ? 'mdi-eye-off' : 'mdi-eye'"
                                            class="cursor-pointer"
                                            @click="showToken = !showToken"
                                        />
                                    </template>
                                </q-input>
                            </div>

                            <div class="col-12 col-md-3">
                                <q-select
                                    v-model="form.fly_token_type"
                                    :options="tokenTypeOptions"
                                    label="Token Type"
                                    filled
                                    emit-value
                                    map-options
                                    :error="!!form.errors.fly_token_type"
                                    :error-message="form.errors.fly_token_type"
                                />
                            </div>

                            <div class="col-12 col-md-3">
                                <q-input
                                    v-model="form.fly_token_expires_at"
                                    label="Token Expiry Date"
                                    filled
                                    type="date"
                                    hint="Optional expiry date"
                                    :error="!!form.errors.fly_token_expires_at"
                                    :error-message="form.errors.fly_token_expires_at"
                                />
                            </div>

                            <div class="col-12">
                                <q-banner class="bg-info text-white" icon="mdi-information">
                                    To create an organization-scoped token, run:
                                    <code class="text-white">fly tokens create org --name "{{ client.name }} Token" --expiry 87600h</code>
                                </q-banner>
                            </div>

                            <div class="col-12">
                                <div class="text-h6 q-mb-md">Contact Information</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <q-input
                                    v-model="form.contact_info.email"
                                    label="Contact Email"
                                    filled
                                    type="email"
                                    :error="!!form.errors['contact_info.email']"
                                    :error-message="form.errors['contact_info.email']"
                                />
                            </div>

                            <div class="col-12 col-md-4">
                                <q-input
                                    v-model="form.contact_info.phone"
                                    label="Contact Phone"
                                    filled
                                    :error="!!form.errors['contact_info.phone']"
                                    :error-message="form.errors['contact_info.phone']"
                                />
                            </div>

                            <div class="col-12 col-md-4">
                                <q-input
                                    v-model="form.contact_info.address"
                                    label="Contact Address"
                                    filled
                                    :error="!!form.errors['contact_info.address']"
                                    :error-message="form.errors['contact_info.address']"
                                />
                            </div>

                            <div class="col-12">
                                <div class="text-h6 q-mb-md">Assign Managers</div>
                                <q-select
                                    v-model="form.user_ids"
                                    :options="userOptions"
                                    label="Select managers who can access this client"
                                    filled
                                    multiple
                                    emit-value
                                    map-options
                                    option-value="value"
                                    option-label="label"
                                    :error="!!form.errors.user_ids"
                                    :error-message="form.errors.user_ids"
                                >
                                    <template v-slot:option="scope">
                                        <q-item v-bind="scope.itemProps">
                                            <q-item-section>
                                                <q-item-label>{{ scope.opt.label }}</q-item-label>
                                                <q-item-label caption>{{ scope.opt.email }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                    </template>
                                </q-select>
                            </div>
                        </div>
                    </q-card-section>

                    <q-separator />

                    <q-card-actions align="right">
                        <q-btn
                            flat
                            label="Cancel"
                            @click="cancel"
                        />
                        <q-btn
                            type="submit"
                            color="primary"
                            label="Update Client"
                            :loading="form.processing"
                        />
                    </q-card-actions>
                </q-form>
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
        client: Object,
        users: Array,
        selectedUserIds: Array,
    },

    data() {
        return {
            form: useForm({
                name: this.client.name,
                fly_org_id: this.client.fly_org_id,
                fly_api_token: '',
                fly_token_type: this.client.fly_token_type || 'org',
                fly_token_expires_at: this.client.fly_token_expires_at || '',
                status: this.client.status,
                billing_start_date: this.client.billing_start_date || '',
                contact_info: {
                    email: this.client.contact_info?.email || '',
                    phone: this.client.contact_info?.phone || '',
                    address: this.client.contact_info?.address || '',
                },
                user_ids: this.selectedUserIds,
            }),
            showToken: false,
            statusOptions: [
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
            ],
            tokenTypeOptions: [
                { label: 'Organization Token', value: 'org' },
                { label: 'Read-only Token', value: 'readonly' },
                { label: 'App-specific Token', value: 'app' },
            ],
        };
    },

    computed: {
        userOptions() {
            return this.users.map(user => ({
                label: user.name,
                value: user.id,
                email: user.email,
            }));
        },
    },

    methods: {
        submit() {
            this.form.put(route('clients.update', this.client.id), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Client updated successfully',
                    });
                },
            });
        },

        cancel() {
            this.$inertia.visit(route('clients.show', this.client.id));
        },
    },
};
</script>