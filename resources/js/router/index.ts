import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const LoginPage = () => import('@/pages/LoginPage.vue');
const DashboardPage = () => import('@/pages/client/DashboardPage.vue');
const PlantsPage = () => import('@/pages/client/PlantsPage.vue');
const ProblemsPage = () => import('@/pages/client/ProblemsPage.vue');
const AccountPage = () => import('@/pages/client/AccountPage.vue');
const PlantDetailPage = () => import('@/pages/client/PlantDetailPage.vue');
const ProblemReportPage = () => import('@/pages/client/ProblemReportPage.vue');

// Layouts
import TechLayout from '@/layouts/TechLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';

// Tech Pages
const TechHomePage = () => import('@/pages/tech/TechHomePage.vue');
const TechMissionPage = () => import('@/pages/tech/TechMissionPage.vue');

function homeByRole(role: string): string {
  switch (role) {
    case 'tech': return '/tech';
    // case 'admin': return '/admin'; 
    case 'client': 
    default: 
      return '/';
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { guestOnly: true, title: 'Connexion' },
    },
    
    // --- TECH ROUTES ---
    {
      path: '/tech',
      component: TechLayout,
      meta: { requiresAuth: true, roles: ['tech'] },
      children: [
        {
          path: '',
          name: 'tech-home',
          component: TechHomePage,
          meta: { title: 'Missions du jour', showBottomNav: true },
        },
        {
          path: 'missions/:id',
          name: 'tech-mission',
          component: TechMissionPage,
          meta: { title: 'Détail Mission', showBottomNav: false },
        }
      ]
    },

    // --- CLIENT ROUTES ---
    {
      path: '/',
      component: ClientLayout,
      meta: { requiresAuth: true, roles: ['client'] },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardPage,
          meta: { title: 'Tableau de bord', showBottomNav: true },
        },
        {
          path: 'plants',
          name: 'plants',
          component: PlantsPage,
          meta: { title: 'Plantes', showBottomNav: true },
        },
        {
          path: 'plants/:id',
          name: 'plant-detail',
          component: PlantDetailPage,
          meta: { title: 'Plante', showBottomNav: false },
        },
        {
          path: 'problems',
          name: 'problems',
          component: ProblemsPage,
          meta: { title: 'Problèmes', showBottomNav: true },
        },
        {
          path: 'problems/:id/report',
          name: 'report-problem',
          component: ProblemReportPage,
          meta: { title: 'Signaler un problème', showBottomNav: false },
        },
        {
          path: 'account',
          name: 'account',
          component: AccountPage,
          meta: { title: 'Mon compte', showBottomNav: true },
        },
      ]
    },
  ],
});

// Protège les routes privées et redirige vers la connexion si nécessaire.
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  if (!authStore.initialized) {
    await authStore.bootstrap();
  }

  const user = authStore.currentUser;
  const isAuthenticated = authStore.isAuthenticated;

  // 1. Not Authenticated but required
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next({ 
      name: 'login', 
      query: { redirect: to.fullPath } 
    });
  }

  // 2. Guest only but Authenticated (e.g. Login page)
  if (to.meta.guestOnly && isAuthenticated && user) {
     return next(homeByRole(user.role));
  }

  // 3. Authenticated but wrong Role
  // Check if route has roles AND if user has one of them.
  // Note: We check if `roles` property exists on the matched record (parent or child)
  // `to.meta` aggregates meta from parent, but let's be safe and check matched.
  // Actually Vue Router 4 merges meta, so checking `to.meta.roles` is usually sufficient if defined on parent.
  
  const requiredRoles = to.meta.roles as string[] | undefined;
  
  if (requiredRoles && isAuthenticated && user) {
    if (!requiredRoles.includes(user.role)) {
       // Redirect to their actual home
       return next(homeByRole(user.role));
    }
  }

  next();
});

// Met à jour le titre de l’onglet navigateur après chaque navigation.
router.afterEach((to) => {
  if (to.meta?.title) {
    document.title = `Potts · ${to.meta.title}`;
  }
});

export default router;
