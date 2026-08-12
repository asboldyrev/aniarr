<template>
    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Библиотека</h1>
            <p class="mt-1 text-sm text-muted-foreground">Все сериалы, которые отслеживает Aniarr</p>
        </div>

        <LibraryToolbar
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            v-model:codec-filter="codecFilter"
            @reset="resetFilters"
        />

        <div v-if="seriesStore.loading && items.length === 0" class="flex min-h-64 items-center justify-center">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <EmptyState
            v-else-if="seriesStore.isEmpty"
            :icon="Tv"
            title="Нет добавленных сериалов"
            description="Добавьте первый сериал через кнопку в навигации"
        />

        <template v-else>
            <div class="flex items-center justify-between gap-3 text-sm text-muted-foreground">
                <p>
                    Показано <span class="font-medium text-foreground">{{ filteredSeries.length }}</span>
                    из {{ items.length }}
                </p>
            </div>

            <LibrarySeriesList v-if="filteredSeries.length > 0" :series="filteredSeries" />

            <EmptyState
                v-else
                :icon="Search"
                title="Ничего не найдено"
                description="Попробуйте изменить поиск или фильтры"
            >
                <template #action>
                    <Button variant="outline" @click="resetFilters">Сбросить фильтры</Button>
                </template>
            </EmptyState>
        </template>
    </div>
</template>

<script setup>
    import { computed, onMounted, ref } from 'vue'
    import { storeToRefs } from 'pinia'
    import { RefreshCw, Search, Tv } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import EmptyState from '@/components/EmptyState.vue'
    import LibrarySeriesList from '@/components/Library/LibrarySeriesList.vue'
    import LibraryToolbar from '@/components/Library/LibraryToolbar.vue'
    import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'
    import useSeriesStore from '@/stores/SeriesStore'
    import {
        getActiveDownload,
        getSeriesCodecState,
        isSeriesIncomplete,
    } from '@/domain/series'

    const seriesStore = useSeriesStore()
    const { items } = storeToRefs(seriesStore)

    const searchQuery = ref('')
    const statusFilter = ref('all')
    const codecFilter = ref('all')

    const filteredSeries = computed(() => {
        const query = searchQuery.value.trim().toLocaleLowerCase('ru-RU')

        return items.value
            .filter((series) => {
                const matchesQuery = query === ''
                    || series.title.toLocaleLowerCase('ru-RU').includes(query)

                const matchesStatus = matchesStatusFilter(series)
                const matchesCodec = codecFilter.value === 'all'
                    || getSeriesCodecState(series) === codecFilter.value

                return matchesQuery && matchesStatus && matchesCodec
            })
            .sort((left, right) => left.title.localeCompare(right.title, 'ru-RU'))
    })

    function matchesStatusFilter(series) {
        if (statusFilter.value === 'all') return true
        if (statusFilter.value === 'monitoring') return Boolean(series.monitored)
        if (statusFilter.value === 'unmonitored') return ! series.monitored
        if (statusFilter.value === 'active') return getActiveDownload(series) !== null
        if (statusFilter.value === 'incomplete') return isSeriesIncomplete(series)

        return true
    }

    function resetFilters() {
        searchQuery.value = ''
        statusFilter.value = 'all'
        codecFilter.value = 'all'
    }

    useRealtimeRefresh(
        () => seriesStore.fetchAll().catch(() => {}),
        { resources: ['series', 'download'], delay: 400 },
    )

    onMounted(() => {
        if (items.value.length === 0) {
            seriesStore.fetchAll().catch(() => {})
        }
    })
</script>
