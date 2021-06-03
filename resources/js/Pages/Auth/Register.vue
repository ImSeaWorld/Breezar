<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">

            <q-card flat bordered>
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center">Register</q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator />
                
                <q-card-section>
                    <q-form @submit.prevent="submit">
                        <q-input 
                            label="Name" 
                            v-model="form.name" 
                            :error-message="errors.name" 
                            :error="errors.name ? true : null"
                        >
                            <template #prepend>
                                <q-icon name="mdi-account" />
                            </template>
                        </q-input>

                        <q-input 
                            label="Email" 
                            v-model="form.email" 
                            :error-message="errors.email"
                            :error="errors.email ? true : null"
                        >
                            <template #prepend>
                                <q-icon name="mdi-email" />
                            </template>
                        </q-input>

                        <q-input 
                            label="Password" 
                            type="password" 
                            v-model="form.password" 
                            :error-message="errors.password"
                            :error="errors.password ? true : null"
                        >
                            <template #prepend>
                                <q-icon name="mdi-key" />
                            </template>
                        </q-input>

                        <q-input 
                            label="Confirm Password" 
                            type="password" 
                            v-model="form.password_confirmation" 
                            :error-message="errors.password"
                            :error="errors.password ? true : null"
                        >
                            <template #prepend>
                                <q-icon name="mdi-key" />
                            </template>
                        </q-input>

                        <div class="q-py-sm text-center">
                            <inertia-link :href="route('login')">
                                Already registered?
                            </inertia-link>
                        </div>

                        <q-btn-group spread>
                            <q-btn label="Register" type="submit" color="secondary" />
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
        errors: Object,
    },

    data() {
        return {
            form: this.$inertia.form({
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                terms: false,
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
            this.form.post(this.route('register'), {
                onFinish: () =>
                    this.form.reset('password', 'password_confirmation'),
            });
        },
    },
};
</script>
