<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Edit User: {{ user.name }}</div>
            </div>

            <q-card>
                <q-form @submit="submit">
                    <q-card-section>
                        <div class="row q-col-gutter-md">
                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.name"
                                    label="Name"
                                    filled
                                    :error="!!form.errors.name"
                                    :error-message="form.errors.name"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.email"
                                    label="Email"
                                    filled
                                    type="email"
                                    :error="!!form.errors.email"
                                    :error-message="form.errors.email"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.password"
                                    label="New Password (leave blank to keep current)"
                                    filled
                                    type="password"
                                    :error="!!form.errors.password"
                                    :error-message="form.errors.password"
                                />
                            </div>

                            <div class="col-12 col-md-6">
                                <q-input
                                    v-model="form.password_confirmation"
                                    label="Confirm New Password"
                                    filled
                                    type="password"
                                    :error="!!form.errors.password_confirmation"
                                    :error-message="form.errors.password_confirmation"
                                />
                            </div>

                            <div class="col-12">
                                <q-select
                                    v-model="form.role"
                                    :options="roleOptions"
                                    label="Role"
                                    filled
                                    emit-value
                                    map-options
                                    :error="!!form.errors.role"
                                    :error-message="form.errors.role"
                                />
                            </div>

                            <div class="col-12" v-if="form.role === 'manager'">
                                <q-select
                                    v-model="form.client_ids"
                                    :options="clientOptions"
                                    label="Assign Clients"
                                    filled
                                    multiple
                                    emit-value
                                    map-options
                                    option-value="value"
                                    option-label="label"
                                    :error="!!form.errors.client_ids"
                                    :error-message="form.errors.client_ids"
                                    hint="Select clients this manager can access"
                                >
                                    <template v-slot:option="scope">
                                        <q-item v-bind="scope.itemProps">
                                            <q-item-section>
                                                <q-item-label>{{ scope.opt.label }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                    </template>
                                </q-select>
                            </div>

                            <div class="col-12" v-if="user.two_factor_secret">
                                <q-checkbox
                                    v-model="form.reset_2fa"
                                    label="Reset Two-Factor Authentication"
                                    color="negative"
                                />
                                <div class="text-caption text-grey-7 q-ml-lg">
                                    This will disable 2FA for this user and require them to set it up again
                                </div>
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
                            label="Update User"
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
        user: Object,
        clients: Array,
        selectedClientIds: Array,
    },

    data() {
        return {
            form: useForm({
                name: this.user.name,
                email: this.user.email,
                password: '',
                password_confirmation: '',
                role: this.user.role,
                client_ids: this.selectedClientIds,
                reset_2fa: false,
            }),
            roleOptions: [
                { label: 'Admin', value: 'admin' },
                { label: 'Manager', value: 'manager' },
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
    },

    methods: {
        submit() {
            this.form.put(route('users.update', this.user.id), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'User updated successfully',
                    });
                },
            });
        },

        cancel() {
            this.$inertia.visit(route('users.index'));
        },
    },
};
</script>