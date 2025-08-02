<template>
  <GuestLayout>
    <q-card class="w-full sm:max-w-md mx-auto">
      <q-card-section>
        <h2 class="text-xl font-semibold text-center mb-6">
          Two-Factor Authentication
        </h2>
        
        <p class="text-sm text-gray-600 mb-6">
          Please enter your 6-digit authentication code to continue.
        </p>

        <q-form @submit="submit">
          <q-input
            v-model="form.code"
            label="Authentication Code"
            mask="######"
            filled
            autofocus
            :error="!!form.errors.code"
            :error-message="form.errors.code"
            class="mb-4"
          />

          <q-btn
            type="submit"
            color="primary"
            label="Verify"
            class="w-full"
            :loading="form.processing"
          />
        </q-form>
      </q-card-section>
    </q-card>
  </GuestLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/Guest.vue';
import { useQuasar } from 'quasar';

const $q = useQuasar();

const form = useForm({
  code: '',
});

const submit = () => {
  form.post(route('2fa.confirm'), {
    onError: () => {
      $q.notify({
        type: 'negative',
        message: 'Invalid authentication code',
      });
      form.reset('code');
    },
  });
};
</script>