<template>
  <q-page class="flex flex-center">
    <div class="login-container q-pa-md">
      <q-card flat bordered class="q-pa-xl">
        <q-card-section class="text-h6 text-center">二段階認証</q-card-section>
        <p class="text-center q-mb-md">登録済みメール／SMSに届いた認証コードを入力してください</p>
        <q-form @submit.prevent="onSubmit" ref="formRef" class="q-gutter-md">
          <q-input
            v-model="code"
            label="認証コード"
            filled
            :rules="[(v: string) => !!v || 'コードは必須です']"
          />
          <q-btn
            type="submit"
            label="認証する"
            color="primary"
            unelevated
            :loading="loading"
            class="full-width"
          />
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
//import { useRouter, useRoute } from 'vue-router';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'stores/auth';
import { Notify } from 'quasar';
import axios from 'axios';
// import { api } from 'boot/axios';

const route = useRoute();
const router = useRouter();
const formRef = ref();
const code = ref('');
const loading = ref(false);
const authStore = useAuthStore();

async function onSubmit() {
  if (!(await formRef.value.validate())) return;
  loading.value = true;
  try {
    const rawUserId = route.query.user_id;
    const user_id = Array.isArray(rawUserId) ? Number(rawUserId[0]) : Number(rawUserId);
    await axios.get('/sanctum/csrf-cookie');
    //await axios.get('/sanctum/csrf-cookie');
    const { data } = await axios.post('/api/login/2fa', { user_id, code: code.value });
    //await axios.post('/api/login/2fa', { user_id, code: code.value });
    authStore.login(user_id, data.token);
    Notify.create({ type: 'positive', message: 'ログイン完了' });
    await router.push({ name: 'Attendance' });
  } catch (err: unknown) {
    if (axios.isAxiosError(err) && err.response?.data?.errors) {
      // 型アサーションで errors の形を指定
      const errs = err.response.data.errors as Record<string, string[]>;
      const messages = Object.values(errs).flat(); // => string[]
      messages.forEach((msg) => {
        Notify.create({ type: 'negative', message: msg });
      });
    } else {
      Notify.create({
        type: 'negative',
        message: '認証に失敗しました。',
      });
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: auto;
}
.full-width {
  width: 100%;
}
</style>
