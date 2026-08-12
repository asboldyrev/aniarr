<template>
    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Загрузки</h1>
            <p class="mt-1 text-sm text-muted-foreground">Активные операции и история загрузок Aniarr</p>
        </div>

        <DownloadsToolbar
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            v-model:trigger-filter="triggerFilter"
            @refresh="fetchItems"
        />

        <div v-if="loading && items.length === 0" class="flex min-h-64 items-center justify-center">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <div v-else-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
            Не удалось загрузить Downloads. Попробуйте обновить страницу.
        </div>

        <template v-else>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-muted-foreground">{{ summaryLabel }}</p>
                <p v-if="meta.total !== undefined" class="text-sm text-muted-foreground">Всего: {{ meta.total }}</p>
            </div>

            <DownloadsList v-if="items.length > 0" :downloads="items" @changed="fetchItems" />

            <EmptyState
                v-else
                :icon="Download"
                title="Загрузок не найдено"
                description="Попробуйте изменить фильтры или дождитесь следующего релиза."
            />

            <DownloadsPagination
                :current-page="currentPage"
                :last-page="lastPage"
                :loading="loading"
                @change="changePage"
            />
        </template>
    </div>
</template>

<script setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
    import { Download, RefreshCw } from '@lucide/vue'
    import DownloadsList from '@/components/Downloads/DownloadsList.vue'
    import DownloadsPagination from '@/components/Downloads/DownloadsPagination.vue'
    import DownloadsToolbar from '@/components/Downloads/DownloadsToolbar.vue'
    import EmptyState from '@/components/EmptyState.vue'
    import { getDownloads } from '@/api/downloads'
    import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'

    const items = ref([])
    const meta = ref({})
    const loading = ref(false)
    const error = ref(null)

    const searchQuery = ref('')
    const statusFilter = ref('all')
    const triggerFilter = ref('all')
    const currentPage = ref(1)
    let searchTimer = null

    const lastPage = computed(() => Number(meta.value.last_page ?? 1))

    const summaryLabel = computed(() => {
        if (statusFilter.value === 'all') {
            return 'Показаны активные и завершённые Downloads'
        }

        return 'Показаны Downloads с выбранным статусом'
    })

    function buildParams() {
        const params = {
            page: currentPage.value,
            per_page: 20,
        }

        const search = searchQuery.value.trim()
        if (search !== '') {
            params.search = search
        }

        if (statusFilter.value !== 'all') {
            params.status = statusFilter.value
        }

        if (triggerFilter.value !== 'all') {
            params.trigger = triggerFilter.value
        }

        return params
    }

    async function fetchItems() {
        loading.value = true
        error.value = null

        try {
            const response = await getDownloads(buildParams())
            items.value = response.items
            meta.value = response.meta

            const availableLastPage = Math.max(1, Number(response.meta.last_page ?? 1))
            if (currentPage.value > availableLastPage) {
                currentPage.value = availableLastPage
            }
        } catch (exception) {
            error.value = exception
        } finally {
            loading.value = false
        }
    }

    function changePage(page) {
        currentPage.value = page
        fetchItems()
    }

    useRealtimeRefresh(fetchItems, { resources: ['download'], delay: 300 })

    watch([statusFilter, triggerFilter], () => {
        currentPage.value = 1
        fetchItems()
    })

    watch(searchQuery, () => {
        if (searchTimer !== null) {
            clearTimeout(searchTimer)
        }

        searchTimer = setTimeout(() => {
            currentPage.value = 1
            fetchItems()
        }, 300)
    })

    onMounted(fetchItems)

    onBeforeUnmount(() => {
        if (searchTimer !== null) {
            clearTimeout(searchTimer)
        }
    })
</script>
