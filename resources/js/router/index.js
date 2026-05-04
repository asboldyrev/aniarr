import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        // {
        //   path: '/',
        //   name: 'dashboard',
        //   component: () => import('../pages/Dashboard.vue'),
        // },
        // {
        //   path: '/series',
        //   name: 'series',
        //   component: () => import('../pages/SeriesIndex.vue'),
        // },
        // {
        //   path: '/series/:id',
        //   name: 'series-detail',
        //   component: () => import('../pages/SeriesDetail.vue'),
        // },
        // {
        //   path: '/downloads',
        //   name: 'downloads',
        //   component: () => import('../pages/ActiveDownloads.vue'),
        // },
        // {
        //   path: '/errors',
        //   name: 'errors',
        //   component: () => import('../pages/Errors.vue'),
        // },
        // {
        //   path: '/settings',
        //   name: 'settings',
        //   component: () => import('../pages/Settings.vue'),
        // },
        // {
        //   path: '/:pathMatch(.*)*',
        //   name: 'not-found',
        //   component: () => import('../pages/NotFound.vue'),
        // },
    ],
});

export default router;
