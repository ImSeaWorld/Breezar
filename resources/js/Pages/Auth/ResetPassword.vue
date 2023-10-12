<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-sm-7 col-md-4 col-lg-3">
            <q-card class="shadow-24">
                <q-toolbar>
                    <q-toolbar-title>Reset Password</q-toolbar-title>
                </q-toolbar>

                <q-separator />

                <q-card-section class="q-pt-sm">
                    <q-tab-panels :model-value="tab">
                        <q-tab-panel name="form" class="q-pa-none">
                            <div class="q-py-sm text-sm text-gray-6">
                                Please provide your new password and confirm it by entering it again. Make sure to choose a secure password that you can easily remember.
                            </div>

                            <form @submit.prevent="submit">
                                <q-input
                                    label="Email"
                                    v-model="form.email"
                                    :error="errors.email ? true : null"
                                    :error-message="errors.email"
                                    :disable="form.processing"
                                >
                                    <template #prepend>
                                        <q-icon name="mdi-email" />
                                    </template>
                                </q-input>

                                <q-input
                                    label="Password"
                                    type="password"
                                    v-model="form.password"
                                    :error="errors.password ? true : null"
                                    :error-message="errors.password"
                                    :disable="form.processing"
                                >
                                    <template #prepend>
                                        <q-icon name="mdi-lock" />
                                    </template>

                                    <template #append>
                                        <q-icon
                                            :name="'mdi-eye' + (showPassword ? '-off' : '')"
                                            class="cursor-pointer"
                                            @click.stop.prevent="showPassword = !showPassword"
                                        />
                                    </template>
                                </q-input>

                                <q-input
                                    label="Password Confirmation"
                                    type="password"
                                    v-model="form.password_confirmation"
                                    :error="errors.password ? true : null"
                                    :error-message="errors.password"
                                    :disable="form.processing"
                                >
                                    <template #prepend>
                                        <q-icon name="mdi-lock" />
                                    </template>

                                    <template #append>
                                        <q-icon
                                            :name="
                                                'mdi-eye' +
                                                (showPasswordConfirm ? '-off' : '')
                                            "
                                            class="cursor-pointer"
                                            @click.stop.prevent="
                                                showPasswordConfirm =
                                                    !showPasswordConfirm
                                            "
                                        />
                                    </template>
                                </q-input>

                                <q-btn-group class="q-my-sm" spread>
                                    <q-btn
                                        no-caps
                                        label="Reset Password"
                                        color="secondary"
                                        type="submit"
                                        :disable="form.processing"
                                    />
                                </q-btn-group>

                                <div class="text-center">
                                    <span
                                        class="text-primary cursor-pointer text-underline"
                                        @click="visit('login')"
                                    >
                                        Remember your password?
                                    </span>
                                </div>
                            </form>
                        </q-tab-panel>
                        <q-tab-panel name="token_error">
                            <div class="text-h4 text-center">
                                <q-icon color="negative" name="mdi-alert-circle" style="vertical-align: baseline;" />
                                Uh oh!
                            </div>

                            <div class="text-h6 text-center q-py-md">
                                {{errors.token ?? 'This password reset link is no longer valid.'}}
                            </div>

                            <div class="text-center">
                                <span
                                    class="text-primary cursor-pointer text-underline"
                                    @click="visit('password.request')"
                                >
                                    Return to Reset Password
                                </span>
                            </div>
                        </q-tab-panel>
                    </q-tab-panels>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';

export default {
    name: 'ResetPasswordPage',
    layout: Guest,
    props: {
        auth: Object,
        email: String,
        errors: Object,
        token: String,
    },
    data() {
        return {
            form: this.$inertia.form({
                token: this.token,
                email: this.email,
                password: '',
                password_confirmation: '',
            }),
            showPassword: false,
            showPasswordConfirm: false,
        };
    },
    methods: {
        submit() {
            this.form.post(this.route('password.update'), {
                onSuccess: () =>
                    this.form.reset('password', 'password_confirmation'),
            });
        },
    },
    computed: {
        tab() {
            return this.errors.token ? 'token_error' : 'form';
        },
    },
};
</script>
