<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">Console: {{ instance.fly_app_id }}</div>
                    <div class="text-caption text-grey-7">
                        <Link :href="route('instances.show', instance.id)" class="text-primary">
                            Back to Instance
                        </Link>
                    </div>
                </div>
            </div>

            <q-card>
                <q-card-section v-if="!consoleUrl">
                    <q-banner class="bg-negative text-white">
                        <template v-slot:avatar>
                            <q-icon name="mdi-alert" />
                        </template>
                        Failed to create console session. Please try again.
                    </q-banner>
                </q-card-section>

                <q-card-section v-else class="q-pa-none">
                    <iframe
                        :src="consoleUrl"
                        style="width: 100%; height: 600px; border: none;"
                        title="Console"
                    />
                </q-card-section>
            </q-card>

            <q-card class="q-mt-md">
                <q-card-section>
                    <div class="text-subtitle1 q-mb-sm">Console Instructions</div>
                    <ul class="q-pl-md">
                        <li>Use standard terminal commands to interact with the machine</li>
                        <li>Type 'exit' to close the session</li>
                        <li>Sessions will timeout after 15 minutes of inactivity</li>
                        <li>For security, avoid running sensitive commands in shared environments</li>
                    </ul>
                </q-card-section>
            </q-card>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Link } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Link,
    },

    props: {
        instance: Object,
        consoleUrl: String,
    },

    mounted() {
        // Check for flash error messages when component loads
        const errorMessage = this.$page.props.flash?.error;
        if (errorMessage) {
            this.$q.notify({
                type: 'negative',
                message: errorMessage,
                timeout: 5000,
            });
        }

        const successMessage = this.$page.props.flash?.success;
        if (successMessage) {
            this.$q.notify({
                type: 'positive',
                message: successMessage,
                timeout: 3000,
            });
        }
    },
};
</script>