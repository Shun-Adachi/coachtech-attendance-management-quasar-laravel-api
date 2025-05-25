<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="q-header bg-black text-white">
      <q-toolbar>
        <q-toolbar-title class="q-toolbar-title">
          <img src="/logo.svg" alt="COACHTECH Logo" height="40" />
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts"></script>

<style scoped>
/* Scoped のまま書くなら deep でクラスを狙う */
:deep(.q-header) {
  padding: 10px;
}

:deep(.q-toolbar-title) {
  padding-left: 50px;
}
</style>
