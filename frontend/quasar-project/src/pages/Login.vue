<template>
  <q-page class="flex flex-center">
    <div class="login-container q-pa-md">
      <q-card flat bordered class="q-pa-xl">
        <q-card-section class="text-h6 text-center">ログイン</q-card-section>
        <q-form @submit.prevent="onSubmit" ref="formRef" class="q-gutter-md">
          <q-input
            v-model="form.email"
            label="メール"
            filled
            type="email"
            :rules="[(v: string) => !!v || '必須です']"
          />
          <q-input
            v-model="form.password"
            label="パスワード"
            filled
            type="password"
            :rules="[(v: string) => !!v || '必須です']"
          />
          <q-btn
            type="submit"
            label="次へ"
            color="primary"
            unelevated
            :loading="loading"
            class="full-width"
          />
        </q-form>

        <q-card-section class="text-center q-mt-sm">
          <RouterLink to="/register">会員登録はこちら</RouterLink>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
defineOptions({ name: 'LoginPage' });
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Notify } from 'quasar';
import axios from 'axios';
//import { api } from 'boot/axios';

const formRef = ref();
const form = ref({ email: '', password: '' });
const loading = ref(false);
const router = useRouter();

async function onSubmit() {
  if (!(await formRef.value.validate())) return;
  loading.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie');
    const { data } = await axios.post('/api/login', form.value);
    await router.push({
      name: 'TwoFactor',
      query: { user_id: data.user_id },
    });
  } catch (err: unknown) {
    // バリデーションエラーなど
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
  width: 100%;
  max-width: 400px;
  margin: auto;
}
.full-width {
  width: 100%;
}
</style>
