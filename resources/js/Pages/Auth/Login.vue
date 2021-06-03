<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">

            <div v-if="status" class="q-mb-sm text-sm text-green-6">
                {{ status }}
            </div>

            <q-card flat bordered>
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center">Login</q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator />

                <q-card-section>
                    <q-form @submit.prevent="submit">
                        <q-input 
                            label="Email" 
                            v-model="form.email"
                            :error="errors.email ? true : null"
                            :error-message="errors.email"
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
                        >
                            <template #prepend>
                                <q-icon name="mdi-key" />
                            </template>
                        </q-input>

                        <div class="row q-py-sm">
                            <div class="col-6">
                                <q-toggle v-model="form.remember" label="Remember Me" />
                            </div>

                            <div class="col-6">
                                <div class="row full-height items-center justify-end">
                                    <inertia-link v-if="canResetPassword" :href="route('password.request')">
                                        Forgot your password?
                                    </inertia-link>
                                </div>
                            </div>
                        </div>

                        <q-btn-group spread>
                            <q-btn label="Login" color="secondary" type="submit" />
                            <q-btn label="Register" type="button" @click.prevent="$inertia.visit('register')" />
                        </q-btn-group>
                    </q-form>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

<script>
import BreezeGuestLayout from '@/Layouts/Guest';
import BreezeValidationErrors from '@/Components/ValidationErrors';

export default {
    layout: BreezeGuestLayout,

    components: {
        BreezeValidationErrors,
    },

    props: {
        auth: Object,
        canResetPassword: Boolean,
        errors: Object,
        status: String,
    },

    data() {
        return {
            form: this.$inertia.form({
                email: '',
                password: '',
                remember: false,
            }),
        };
    },

    computed: {
        errors() {
            return this.$page.props.errors;
        },
    },

    methods: {
        submit() {
            this.form.post(this.route('login'), {
                onFinish: () => this.form.reset('password'),
            });
        },
    },
};
</script>
