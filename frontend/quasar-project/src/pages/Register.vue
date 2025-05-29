<template>
  <q-page class="flex flex-center">
    <div class="register-container q-pa-md">
      <q-card flat bordered class="q-pa-xl">
        <q-card-section class="text-h6 text-center"> 会員登録 </q-card-section>

        <q-form ref="formRef" @submit.prevent="onSubmit" class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="名前"
            filled
            :rules="[(val: string) => !!val || '名前は必須です']"
          />

          <q-input
            v-model="form.email"
            label="メールアドレス"
            filled
            type="email"
            :rules="[
              (val: string) => !!val || 'メールアドレスは必須です',
              (val: string) => /.+@.+\..+/.test(val) || '有効なメールアドレスを入力してください',
            ]"
          />

          <q-input
            v-model="form.password"
            label="パスワード"
            filled
            type="password"
            :rules="[(val: string) => (val && val.length >= 6) || '8文字以上で入力してください']"
          />

          <q-input
            v-model="form.password_confirmation"
            label="パスワード確認"
            filled
            type="password"
            :rules="[
              (val: string) => !!val || '確認用パスワードは必須です',
              (val: string) => val === form.password || 'パスワードが一致しません',
            ]"
          />

          <q-btn
            type="submit"
            label="登録する"
            color="primary"
            unelevated
            :loading="isSubmitting"
            class="full-width"
          />
        </q-form>

        <q-card-section class="text-center q-mt-sm">
          <RouterLink to="/login">ログインはこちら</RouterLink>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
defineOptions({ name: 'RegisterPage' });
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Notify } from 'quasar';
import axios from 'axios';

// フォームの状態
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const isSubmitting = ref(false);
const formRef = ref();

const router = useRouter();

/**
 * フォーム送信時のハンドラ
 */
const onSubmit = async () => {
  // バリデーション
  const valid = await formRef.value?.validate();
  if (!valid) {
    return;
  }

  isSubmitting.value = true;
  try {
    // Laravel API を呼び出し
    await axios.post('/api/register', form.value);

    Notify.create({
      type: 'positive',
      message: '登録が完了しました。ログインページへ移動します。',
    });
    await router.push('/login');
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
        message: '登録に失敗しました。',
      });
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.register-container {
  width: 100%;
  max-width: 400px;
  margin: auto;
}
.full-width {
  width: 100%;
}
</style>
