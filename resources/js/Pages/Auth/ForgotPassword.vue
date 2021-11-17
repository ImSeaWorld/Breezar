<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">

            <div v-if="status" class="q-mb-sm font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <q-card flat bordered>
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center">Forgot Password</q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator />
                
                <div class="q-pt-md q-pr-md q-pl-md q-pb-none text-sm text-gray-6">
                    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
                </div>

                <q-card-section class="q-pt-sm">
                    <q-form @submit.prevent="submit">
                        <q-input 
                            label="Email" 
                            v-model="form.email"
                        >
                            <template #prepend>
                                <q-icon name="mdi-email" />
                            </template>
                        </q-input>

                        <div class="flex items-center justify-end q-mt-sm">
                            <inertia-link :href="route('login')" class="q-mr-md">
                                Login
                            </inertia-link>
                            <inertia-link :href="route('password.confirm')" class="q-mr-md">
                                Secret krabby patty recipe
                            </inertia-link>
                            <q-btn 
                                label="Email Password Reset Link" 
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
            form: this.$inertia.form({
                email: '',
            }),
        };
    },
    methods: {
        submit() {
            this.form.post(this.route('password.email'));
        },
    },
};
</script>
