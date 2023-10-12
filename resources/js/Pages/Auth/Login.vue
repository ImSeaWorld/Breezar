<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">
            <q-card class="shadow-24">
                <q-card v-if="errors.account">
                    <q-card-section class="bg-negative text-center text-white">
                        {{ errors.account }}
                    </q-card-section>
                </q-card>

                <q-card v-if="success">
                    <q-card-section class="bg-positive text-center text-white">
                        Success! Redirecting...
                    </q-card-section>
                </q-card>
                
                <div class="q-px-lg q-pt-lg text-center">
                    <ApplicationLogo style="height: 85px;width: auto;" />
                </div>

                <q-card-section>
                    <q-form @submit.prevent="submit">
                        <q-input
                            label="Email"
                            v-model="form.email"
                            :error="errors.email ? true : null"
                            :error-message="errors.email"
                            @keyup.ctrl.enter="submit"
                            tabindex="1"
                        >
                            <template #prepend>
                                <q-icon name="mdi-email" />
                            </template>
                        </q-input>

                        <q-input
                            label="Password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            :error="errors.password ? true : null"
                            :error-message="errors.password"
                            @keyup.ctrl.enter="submit"
                            tabindex="2"
                        >
                            <template #prepend>
                                <q-icon name="mdi-lock" />
                            </template>

                            <template #append>
                                <q-icon
                                    :name="
                                        'mdi-eye' + (showPassword ? '-off' : '')
                                    "
                                    class="cursor-pointer"
                                    @click.stop.prevent="
                                        showPassword = !showPassword
                                    "
                                />
                            </template>
                        </q-input>

                        <div class="row q-py-sm">
                            <div class="col-6">
                                <q-toggle
                                    v-model="form.remember"
                                    label="Remember Me"
                                    tabindex="3"
                                    color="secondary"
                                />
                            </div>

                            <div class="col-6">
                                <div
                                    v-if="canResetPassword"
                                    class="row full-height items-center justify-end"
                                >
                                    <a
                                        class="text-primary"
                                        :href="route('password.request')"
                                        tabindex="5"
                                    >
                                        Forgot your password?
                                    </a>
                                </div>
                            </div>
                        </div>

                        <q-btn-group spread>
                            <q-btn
                                no-caps
                                label="Login"
                                color="primary"
                                type="submit"
                                tabindex="4"
                            />
                            <q-btn 
                                no-caps
                                label="Register"
                                type="button"
                                @click.prevent="$inertia.visit('register')"
                                v-if="canRegister"
                            />
                        </q-btn-group>
                    </q-form>

                    <q-item-section class="q-mt-md">
                        <q-item-label
                            caption
                            class="text-center cursor-pointer text-secondary"
                            @click="windowOpen('https://laravel.com/')"
                        >
                            &copy; 2022-{{ new Date().getFullYear() }}
                        </q-item-label>
                    </q-item-section>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

export default {
    layout: Guest,
    props: {
        auth: Object,
        errors: Object,
        canResetPassword: {
            type: Boolean,
            default: false,
        },
        canRegister: {
            type: Boolean,
            default: false,
        },
    },
    components: {
        ApplicationLogo,
    },
    data() {
        return {
            loading: false,
            success: false,
            showPassword: false,
            form: this.$inertia.form({
                email: '',
                password: '',
                remember: false,
            }),
        };
    },
    methods: {
        submit() {
            this.loading = true;

            this.form.post(this.route('login'), {
                onSuccess: () => {
                    this.success = true;
                },
                onFinish: () => {
                    this.form.reset('password');
                    this.loading = false;
                },
            });
        },
    },
};
</script>