<template>
    <div class="space-y-5 p-4 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Библиотека</h1>
            <p class="mt-1 text-sm text-muted-foreground">Все добавленные сериалы и их текущее состояние</p>
        </div>

        <LibraryToolbar
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
        />

        <div v-if="seriesStore.loading" class="flex min-h-64 items-center justify-center">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <EmptyState
            v-else-if="seriesStore.isEmpty"
            :icon="Tv"
            title="Нет добавленных сериалов"
            description="Добавьте первый сериал через кнопку в навигации"
        />

        <SeriesTable v-else-if="filteredSeries.length > 0" :series="filteredSeries" />

        <EmptyState
            v-else
            :icon="Search"
            title="Ничего не найдено"
            description="Попробуйте изменить поиск или фильтр"
        >
            <template #action>
                <Button variant="outline" @click="resetFilters">Сбросить фильтры</Button>
            </template>
        </EmptyState>
    </div>
</template>

<script setup>
    import { computed, onMounted, ref } from 'vue'
    import { storeToRefs } from 'pinia'
    import { RefreshCw, Search, Tv } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import EmptyState from '@/components/EmptyState.vue'
    import LibraryToolbar from '@/components/Library/LibraryToolbar.vue'
    import SeriesTable from '@/components/SeriesTable.vue'
    import useSeriesStore from '@/stores/SeriesStore'
    import { getSeriesUiStatus } from '@/domain/series'

    const seriesStore = useSeriesStore()
    const { items } = storeToRefs(seriesStore)

    const searchQuery = ref('')
    const statusFilter = ref('all')

    const filteredSeries = computed(() => {
        const query = searchQuery.value.trim().toLocaleLowerCase('ru-RU')

        return items.value.filter((series) => {
            const matchesQuery = query === '' || series.title.toLocaleLowerCase('ru-RU').includes(query)
            const matchesStatus = statusFilter.value === 'all' || getSeriesUiStatus(series) === statusFilter.value

            return matchesQuery && matchesStatus
        })
    })

    function resetFilters() {
        searchQuery.value = ''
        statusFilter.value = 'all'
    }

    onMounted(() => {
        if (items.value.length === 0) {
            seriesStore.fetchAll().catch(() => {})
        }
    })
</script>
