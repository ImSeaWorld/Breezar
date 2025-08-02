<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Two-Factor Authentication
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <q-card class="p-6">
          <q-card-section>
            <h3 class="text-lg font-medium text-gray-900">
              {{ enabled ? 'Two-Factor Authentication is Enabled' : 'Enable Two-Factor Authentication' }}
            </h3>
            <p class="mt-1 text-sm text-gray-600">
              {{ enabled 
                ? 'Two-factor authentication adds an additional layer of security to your account.'
                : 'When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication.'
              }}
            </p>
          </q-card-section>

          <q-card-section v-if="!enabled">
            <q-btn 
              color="primary" 
              label="Enable 2FA" 
              @click="enable2FA"
              :loading="loading"
            />
          </q-card-section>

          <q-card-section v-else>
            <q-form @submit="disable2FA" class="space-y-4">
              <q-input
                v-model="form.code"
                label="Authentication Code"
                hint="Enter your 6-digit authentication code to disable 2FA"
                :error="!!form.errors.code"
                :error-message="form.errors.code"
                mask="######"
                filled
              />
              <q-btn 
                type="submit" 
                color="negative" 
                label="Disable 2FA"
                :loading="form.processing"
              />
            </q-form>
          </q-card-section>
        </q-card>

        <q-dialog v-model="showQRDialog" persistent>
          <q-card style="min-width: 400px">
            <q-card-section>
              <div class="text-h6">Set Up Two-Factor Authentication</div>
            </q-card-section>

            <q-card-section class="text-center">
              <p class="mb-4">Scan this QR code with your authenticator app:</p>
              <img :src="qrCode" alt="2FA QR Code" class="mx-auto mb-4" v-if="qrCode" />
              <p class="text-sm text-gray-600 mb-2">Or enter this code manually:</p>
              <code class="bg-gray-100 px-2 py-1 rounded">{{ secret }}</code>
            </q-card-section>

            <q-card-actions align="right">
              <q-btn flat label="Done" color="primary" @click="showQRDialog = false" />
            </q-card-actions>
          </q-card>
        </q-dialog>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { useQuasar } from 'quasar';

const props = defineProps({
  enabled: Boolean,
});

const $q = useQuasar();
const page = usePage();
const loading = ref(false);
const showQRDialog = ref(false);
const qrCode = ref('');
const secret = ref('');

const form = useForm({
  code: '',
});

const enable2FA = async () => {
  loading.value = true;
  
  try {
    await form.post(route('2fa.enable'), {
      preserveScroll: true,
      onSuccess: (page) => {
        if (page.props.flash.qrCode) {
          qrCode.value = page.props.flash.qrCode;
          secret.value = page.props.flash.secret;
          showQRDialog.value = true;
        }
        $q.notify({
          type: 'positive',
          message: '2FA has been enabled successfully',
        });
      },
      onError: () => {
        $q.notify({
          type: 'negative',
          message: 'Failed to enable 2FA',
        });
      },
    });
  } finally {
    loading.value = false;
  }
};

const disable2FA = () => {
  form.post(route('2fa.disable'), {
    preserveScroll: true,
    onSuccess: () => {
      $q.notify({
        type: 'positive',
        message: '2FA has been disabled',
      });
      form.reset();
    },
    onError: () => {
      $q.notify({
        type: 'negative',
        message: 'Invalid authentication code',
      });
    },
  });
};
</script>