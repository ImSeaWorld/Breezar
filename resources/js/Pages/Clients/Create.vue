<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Create New Client</div>
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
                            label="Create Client"
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
        users: Array,
    },

    data() {
        return {
            form: useForm({
                name: '',
                fly_org_id: '',
                status: 'active',
                billing_start_date: '',
                contact_info: {
                    email: '',
                    phone: '',
                    address: '',
                },
                user_ids: [],
            }),
            statusOptions: [
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
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
            this.form.post(route('clients.store'), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Client created successfully',
                    });
                },
            });
        },

        cancel() {
            this.$inertia.visit(route('clients.index'));
        },
    },
};
</script>