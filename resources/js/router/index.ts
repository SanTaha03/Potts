import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const DevicesPage = () => import('@/pages/DevicesPage.vue');
const LoginPage = () => import('@/pages/LoginPage.vue');

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { guestOnly: true, title: 'Connexion' },
    },
    {
      path: '/',
      name: 'devices',
      component: DevicesPage,
      meta: { requiresAuth: true, title: 'Devices' },
    },
  ],
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();
  if (!authStore.initialized) {
    await authStore.bootstrap();
  }

  if (to.meta?.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    };
  }

  if (to.meta?.guestOnly && authStore.isAuthenticated) {
    return { name: 'devices' };
  }

  return true;
});

router.afterEach((to) => {
  if (to.meta?.title) {
    document.title = `Potts · ${to.meta.title}`;
  }
});

export default router;
