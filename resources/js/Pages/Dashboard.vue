<template>
    <Authenticated :auth="auth">
        <template #header>
            <span class="text-h6 text-center">
                Dashboard
            </span>
        </template>

        <div class="q-py-md">
            <!--  -->
        </div>
    </Authenticated>
</template>

<script>
import Authenticated from '@/Layouts/Authenticated';

export default {
    components: {
        Authenticated,
    },
    props: {
        auth: Object,
        errors: Object,
    },
};
</script>
