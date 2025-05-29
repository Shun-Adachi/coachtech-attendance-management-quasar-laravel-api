import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: localStorage.getItem('token') ? true : false,
    userId: localStorage.getItem('userId') ? Number(localStorage.getItem('userId')) : null,
  }),
  actions: {
    login(userId: number, token: string) {
      this.isAuthenticated = true;
      this.userId = userId;
      localStorage.setItem('token', token);
      localStorage.setItem('userId', userId.toString());
    },
    logout() {
      this.isAuthenticated = false;
      this.userId = null;
      localStorage.removeItem('token');
      localStorage.removeItem('userId');
    },
  },
});
