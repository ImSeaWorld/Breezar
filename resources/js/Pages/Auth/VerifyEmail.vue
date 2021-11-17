<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">

            <q-card flat bordered>
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center">Forgot Password</q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator />
                
                <div class="q-pt-md q-pr-md q-pl-md q-pb-none text-sm text-gray-6">
                    Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                </div>

                <div class="q-pt-md q-pr-md q-pl-md q-pb-none text-sm text-gray-6" v-if="verificationLinkSent" >
                    A new verification link has been sent to the email address you provided during registration.
                </div>

                <q-card-section class="q-pt-sm">
                    <q-form @submit.prevent="submit">
                        <div class="flex items-center justify-end q-mt-sm">
                            <q-btn 
                                label="Resend Verification Email" 
                                color="secondary"
                                type="submit" 
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                            />
                        </div>
                    </q-form>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

<script>
import Guest from '@/Layouts/Guest';

export default {
    layout: Guest,
    props: {
        auth: Object,
        errors: Object,
        status: String,
    },
    data() {
        return {
            form: this.$inertia.form(),
        };
    },
    methods: {
        submit() {
            this.form.post(this.route('verification.send'));
        },
    },
    computed: {
        verificationLinkSent() {
            return this.status === 'verification-link-sent';
        },
    },
};
</script>
