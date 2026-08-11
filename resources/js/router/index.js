import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: () => import('@/pages/Dashboard.vue'),
        },
        {
            path: '/library',
            name: 'library',
            component: () => import('@/pages/Library.vue'),
        },
        {
            path: '/series/:id',
            name: 'series-detail',
            component: () => import('@/pages/SeriesDetail.vue'),
        },
        {
            path: '/downloads',
            name: 'downloads',
            component: () => import('@/pages/ActiveDownloads.vue'),
        },
        {
            path: '/activity',
            name: 'activity',
            component: () => import('@/pages/Errors.vue'),
        },
        {
            path: '/errors',
            redirect: '/activity',
        },
        {
            path: '/settings',
            name: 'settings',
            component: () => import('@/pages/Settings.vue'),
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('@/pages/NotFound.vue'),
        },
    ],
})

export default router
