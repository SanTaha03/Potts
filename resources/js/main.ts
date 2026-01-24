import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from '@/stores/authStore';
import { http, resetCsrfState } from '@/services/http';
import { Icon } from "@iconify/vue";

import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

const authStore = useAuthStore(pinia);
authStore.bootstrap().catch(() => undefined);

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      authStore.setUser(null);
      authStore.clearError();
      resetCsrfState();
      if (router.currentRoute.value.name !== 'login') {
        const redirect = router.currentRoute.value.fullPath ?? '/';
        router.push({ name: 'login', query: { redirect } }).catch(() => undefined);
      }
    }
    return Promise.reject(error);
  }
);

app.use(router);

app.mount('#app');
