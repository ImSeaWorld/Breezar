<template>
    <div class="absolute-full">
        <slot />
    </div>
</template>

<script>
import BreezeApplicationLogo from '@/Components/ApplicationLogo';

export default {
    components: {
        BreezeApplicationLogo,
    },
};
</script>
