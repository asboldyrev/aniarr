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
            component: () => import('@/pages/Downloads.vue'),
        },
        {
            path: '/activity',
            name: 'activity',
            component: () => import('@/pages/Activity.vue'),
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

const staleChunkReloadKey = 'aniarr:stale-chunk-reload'

router.onError((error) => {
    const message = String(error?.message ?? error)
    const isStaleChunk = message.includes('Failed to fetch dynamically imported module')
        || message.includes('Importing a module script failed')
        || message.includes('error loading dynamically imported module')

    if (! isStaleChunk) {
        return
    }

    if (sessionStorage.getItem(staleChunkReloadKey) === '1') {
        sessionStorage.removeItem(staleChunkReloadKey)
        return
    }

    sessionStorage.setItem(staleChunkReloadKey, '1')
    window.location.reload()
})

router.afterEach(() => {
    sessionStorage.removeItem(staleChunkReloadKey)
})

export default router
