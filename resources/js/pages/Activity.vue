<template>
    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Activity</h1>
            <p class="mt-1 text-sm text-muted-foreground">События Aniarr, предупреждения и ошибки интеграций</p>
        </div>

        <ActivityToolbar
            v-model:type-filter="typeFilter"
            v-model:state-filter="stateFilter"
            v-model:source-filter="sourceFilter"
            @refresh="fetchItems"
        />

        <div v-if="loading && items.length === 0" class="flex min-h-64 items-center justify-center">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <div v-else-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
            Не удалось загрузить Activity. Попробуйте обновить страницу.
        </div>

        <template v-else>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ summaryLabel }}
                </p>
                <p v-if="meta.total !== undefined" class="text-sm text-muted-foreground">
                    Всего: {{ meta.total }}
                </p>
            </div>

            <ActivityList
                v-if="items.length > 0"
                :items="items"
                :busy-ids="busyIds"
                @resolve="handleResolve"
                @reopen="handleReopen"
            />

            <EmptyState
                v-else
                :icon="CheckCircle"
                :title="emptyTitle"
                :description="emptyDescription"
                icon-class-name="text-green-500"
            />

            <ActivityPagination
                :current-page="currentPage"
                :last-page="lastPage"
                :loading="loading"
                @change="changePage"
            />
        </template>
    </div>
</template>

<script setup>
    import { computed, onMounted, ref, watch } from 'vue'
    import { CheckCircle, RefreshCw } from '@lucide/vue'
    import ActivityList from '@/components/Activity/ActivityList.vue'
    import ActivityPagination from '@/components/Activity/ActivityPagination.vue'
    import ActivityToolbar from '@/components/Activity/ActivityToolbar.vue'
    import EmptyState from '@/components/EmptyState.vue'
    import { getActivity, reopenActivity, resolveActivity } from '@/api/activity'

    const items = ref([])
    const meta = ref({})
    const loading = ref(false)
    const error = ref(null)
    const busyIds = ref(new Set())

    const typeFilter = ref('all')
    const stateFilter = ref('unresolved')
    const sourceFilter = ref('all')
    const currentPage = ref(1)

    const lastPage = computed(() => Number(meta.value.last_page ?? 1))

    const summaryLabel = computed(() => {
        if (stateFilter.value === 'unresolved') {
            return 'Показаны нерешённые предупреждения и ошибки'
        }

        return 'Показана история событий Aniarr'
    })

    const emptyTitle = computed(() => stateFilter.value === 'unresolved'
        ? 'Всё в порядке'
        : 'Событий не найдено')

    const emptyDescription = computed(() => stateFilter.value === 'unresolved'
        ? 'Нерешённых предупреждений и ошибок нет.'
        : 'Попробуйте изменить фильтры Activity.')

    function buildParams() {
        const params = {
            page: currentPage.value,
            per_page: 20,
        }

        if (typeFilter.value !== 'all') {
            params.type = typeFilter.value
        }

        if (sourceFilter.value !== 'all') {
            params.source = sourceFilter.value
        }

        if (stateFilter.value === 'unresolved') {
            params.unresolved = 1
        }

        return params
    }

    async function fetchItems() {
        loading.value = true
        error.value = null

        try {
            const response = await getActivity(buildParams())
            items.value = response.items
            meta.value = response.meta

            if (currentPage.value > Number(response.meta.last_page ?? 1)) {
                currentPage.value = Math.max(1, Number(response.meta.last_page ?? 1))
            }
        } catch (exception) {
            error.value = exception
        } finally {
            loading.value = false
        }
    }

    function setBusy(id, state) {
        const next = new Set(busyIds.value)

        if (state) {
            next.add(id)
        } else {
            next.delete(id)
        }

        busyIds.value = next
    }

    async function handleResolve(item) {
        setBusy(item.id, true)

        try {
            await resolveActivity(item.id)
            await fetchItems()
        } finally {
            setBusy(item.id, false)
        }
    }

    async function handleReopen(item) {
        setBusy(item.id, true)

        try {
            await reopenActivity(item.id)
            await fetchItems()
        } finally {
            setBusy(item.id, false)
        }
    }

    function changePage(page) {
        currentPage.value = page
        fetchItems()
    }

    watch([typeFilter, stateFilter, sourceFilter], () => {
        currentPage.value = 1
        fetchItems()
    })

    onMounted(fetchItems)
</script>
