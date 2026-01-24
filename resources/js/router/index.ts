import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const LoginPage = () => import('@/pages/LoginPage.vue');
const DashboardPage = () => import('@/pages/DashboardPage.vue');
const PlantsPage = () => import('@/pages/PlantsPage.vue');
const ProblemsPage = () => import('@/pages/ProblemsPage.vue');
const AccountPage = () => import('@/pages/AccountPage.vue');
const PlantDetailPage = () => import('@/pages/PlantDetailPage.vue');
const ProblemReportPage = () => import('@/pages/ProblemReportPage.vue');

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
      name: 'dashboard',
      component: DashboardPage,
      meta: { requiresAuth: true, title: 'Tableau de bord' },
    },
    {
      path: '/plants',
      name: 'plants',
      component: PlantsPage,
      meta: { requiresAuth: true, title: 'Plantes' },
    },
    {
      path: '/plants/:id',
      name: 'plant-detail',
      component: PlantDetailPage,
      meta: { requiresAuth: true, title: 'Plante' },
    },
    {
      path: '/problems',
      name: 'problems',
      component: ProblemsPage,
      meta: { requiresAuth: true, title: 'Problèmes' },
    },
    {
      path: '/problems/:id/report',
      name: 'report-problem',
      component: ProblemReportPage,
      meta: { requiresAuth: true, title: 'Signaler un problème' },
    },
    {
      path: '/account',
      name: 'account',
      component: AccountPage,
      meta: { requiresAuth: true, title: 'Mon compte' },
    },
  ],
});

// Protège les routes privées et redirige vers la connexion si nécessaire.
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
    return { name: 'dashboard' };
  }

  return true;
});

// Met à jour le titre de l’onglet navigateur après chaque navigation.
router.afterEach((to) => {
  if (to.meta?.title) {
    document.title = `Potts · ${to.meta.title}`;
  }
});

export default router;
