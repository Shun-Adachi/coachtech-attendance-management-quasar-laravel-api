<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="q-header bg-black text-white">
      <q-toolbar>
        <q-toolbar-title class="q-toolbar-title">
          <img src="/logo.svg" alt="COACHTECH Logo" height="40" />
        </q-toolbar-title>

        <template v-if="authStore.isAuthenticated">
          <q-btn flat label="勤怠" to="/attendance" />
          <q-btn flat label="勤怠一覧" to="/attendance-list" />
          <q-btn flat label="申請" to="/application" />
          <q-btn flat label="ログアウト" @click="logout" />
        </template>
        <div>
          <span v-if="authStore.isAuthenticated">ログイン中</span>
          <span v-else>未ログイン</span>
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from 'stores/auth';
const router = useRouter();
const authStore = useAuthStore();
const logout = () => {
  // ログアウト処理の追加
  // 例えば、セッションやトークンの削除など

  authStore.logout();
  localStorage.removeItem('token');
  void router.push({ name: 'Login' }); // ログインページにリダイレクト
};
</script>

<style scoped>
:deep(.q-header) {
  padding: 10px;
}
</style>
