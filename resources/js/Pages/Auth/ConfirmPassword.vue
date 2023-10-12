<template>
    <div class="row justify-center align-center items-center full-height">
        <div class="col-11 col-md-4 col-lg-3">
            <q-card class="shadow-24">
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-center"
                            >Forgot Password</q-item-label
                        >
                    </q-item-section>
                </q-item>

                <q-separator />

                <div
                    class="q-pt-md q-pr-md q-pl-md q-pb-none text-sm text-gray-6"
                >
                    This is a secure area of the application. Please confirm
                    your password before continuing.
                </div>

                <q-card-section class="q-pt-sm">
                    <q-form @submit.prevent="submit">
                        <q-input
                            label="Password"
                            type="password"
                            v-model="form.password"
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

                        <div class="flex items-center justify-end q-mt-sm">
                            <q-btn
                                label="Confirm"
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
import Guest from '@/Layouts/Guest.vue';

export default {
    layout: Guest,
    props: {
        auth: Object,
        errors: Object,
    },
    data() {
        return {
            form: this.$inertia.form({
                password: '',
            }),
            showPassword: false,
        };
    },
    methods: {
        submit() {
            this.form.post(this.route('password.confirm'), {
                onFinish: () => this.form.reset(),
            });
        },
    },
};
</script>