<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">

            <q-card class="shadow-24">
                <q-item>
                    <q-item-section class="text-h5 text-center">
                        Forgot password
                    </q-item-section>
                </q-item>

                <q-separator />

                <q-tab-panels :model-value="tab" animated>
                    <q-tab-panel name="form" class="q-pa-none">
                        <div class="q-pt-md q-pr-md q-pl-md q-pb-none text-sm text-gray-6">
                            Please fill in your email address and we will send you a
                            link to reset your password.
                        </div>

                        <q-card-section class="q-pt-sm">
                            <q-form @submit.prevent="submit">
                                <q-input
                                    label="Email"
                                    v-model="form.email"
                                    @keyup.ctrl.enter="submit"
                                    @keyup.enter="submit"
                                    :error="errors.email ? true : null"
                                    :error-message="errors.email ?? ''"
                                >
                                    <template #prepend>
                                        <q-icon name="mdi-email" />
                                    </template>
                                </q-input>

                                <q-btn-group class="q-my-sm" spread>
                                    <q-btn
                                        no-caps
                                        label="Email Reset Link"
                                        color="secondary"
                                        type="submit"
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
                            </q-form>
                        </q-card-section>
                    </q-tab-panel>
                    <q-tab-panel name="status" class="q-pa-none">
                        <q-card-section class="q-pt-sm">
                            <div class="text-center text-h6 q-mt-sm">
                                <q-icon size="lg" name="mdi-email-check-outline" class="text-positive" /> Reset Link Sent
                            </div>
                            <div class="text-center text-sm text-gray-6 q-mt-sm">
                                {{ status }}
                            </div>
                            <div class="text-center text-sm text-gray-6 q-mt-sm">
                                If you do not receive an email within 5 minutes, please try again.
                            </div>

                            <q-btn-group class="q-my-sm q-pt-md" spread>
                                <q-btn
                                    no-caps
                                    color="secondary"
                                    label="Login"
                                    @click="visit('login')"
                                />
                            </q-btn-group>

                            <div class="text-center">
                                <span
                                    @click="(countdownActive ? undefined : submit())"
                                    :class="['text-' + (countdownActive ? 'grey-7' : 'primary cursor-pointer text-underline')]"
                                >
                                    Resend Email
                                    <span v-if="countdownActive">
                                        (wait {{ resendTimer.seconds }}s)
                                    </span>
                                </span>
                            </div>
                        </q-card-section>
                    </q-tab-panel>
                </q-tab-panels>
            </q-card>
        </div>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest.vue';

export default {
    name: 'ForgotPasswordPage',
    layout: Guest,
    props: {
        auth: Object,
        errors: Object,
        status: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            resendTimer:{
                seconds: 60,
                handle: null,
                initiated: false,
            },
            form: this.$inertia.form({
                email: '',
            }),
        };
    },
    methods: {
        submit() {
            // callbacks :(
            const $this = this;

            this.form.post(this.route('password.email'), {
                onSuccess() {
                    $this.initiateCount();
                }
            });
        },
        initiateCount() {
            if (this.resendTimer.initiated) return;

            this.resendTimer.seconds = 60;
            this.resendTimer.initiated = true;
            this.resendTimer.handle = setInterval(this.countDown, 1000);
        },
        countDown() {
            if (this.resendTimer.seconds > 0) {
                this.resendTimer.seconds--;
                return;
            }

            this.resendTimer.initiated = false;
            clearInterval(this.resendTimer.handle);
        },
    },
    computed: {
        tab() {
            return ((this.status?.length ?? 0) > 0) ? 'status' : 'form';
        },
        countdownActive() {
            return this.resendTimer.seconds > 0;
        },
    },
    watch: {
        tab() {
            if (this.tab === 'status') {
                this.initiateCount();
            }
        },
    },
    beforeUnmount() {
        clearInterval(this.resendTimer.handle);
    },
}
</script>
