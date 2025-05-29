import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },

      {
        path: 'register',
        name: 'Register',
        component: () => import('pages/Register.vue'),
      },
      {
        path: 'login',
        name: 'Login',
        component: () => import('pages/Login.vue'),
      },
      {
        path: 'login/2fa',
        name: 'TwoFactor',
        component: () => import('pages/TwoFactor.vue'),
      },
      {
        path: 'attendance',
        name: 'Attendance',
        component: () => import('pages/Attendance.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
];

export default routes;
