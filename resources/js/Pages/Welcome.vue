<template>
    <div class="bg-primary">
        <div v-if="canLogin" class="fixed top-0 right-0 q-px-md q-py-sm">
            <inertia-link v-if="$page.props.auth.user" href="/dashboard" class="text-sm text-gray-7 underline">
                Dashboard
            </inertia-link>

            <template v-else>
                <inertia-link :href="route('login')" class="text-sm text-gray-7 underline">
                    Log in
                </inertia-link>

                <inertia-link v-if="canRegister" :href="route('register')" class="q-ml-sm text-sm text-gray-7 underline">
                    Register
                </inertia-link>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        auth: Object,
        canLogin: Boolean,
        canRegister: Boolean,
        errors: Object,
        laravelVersion: String,
        phpVersion: String,
    },
};
</script>
