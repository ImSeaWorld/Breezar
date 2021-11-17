<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">
            <q-card flat bordered>
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center">Reset Password</q-item-label>
                    </q-item-section>
                </q-item>

                <q-separator />

                <q-card-section>
                    <form @submit.prevent="submit">
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
                        
                        <q-input 
                            label="Password Confirmation" 
                            type="password" 
                            v-model="form.password_confirmation"
                            :error="errors.password ? true : null"
                            :error-message="errors.password"
                        >
                            <template #prepend>
                                <q-icon name="mdi-key" />
                            </template>
                        </q-input>

                        <div class="flex items-center justify-end q-mt-sm">
                            <q-btn 
                                label="Reset Password" 
                                color="secondary"
                                type="submit" 
                                :disabled="form.processing"
                                :class="{ 'opacity-25': form.processing }"
                            />
                        </div>
                    </form>
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
        };
    },
    methods: {
        submit() {
            this.form.post(this.route('password.update'), {
                onFinish: () =>
                    this.form.reset('password', 'password_confirmation'),
            });
        },
    },
};
</script>
