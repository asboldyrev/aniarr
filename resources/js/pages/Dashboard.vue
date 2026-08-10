<template>
    <div v-if="seriesStore.loading" class="flex h-full items-center justify-center p-8">
        <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="seriesStore.isEmpty" class="flex h-full items-center justify-center p-8">
        <EmptyState :icon="Tv" title="Нет добавленных сериалов" description="Добавьте первый сериал, чтобы начать отслеживание обновлений и автоматическую загрузку">
            <template #action>
                <Button class="gap-2 cursor-pointer">
                    <PlusCircle class="h-4 w-4" />
                    Добавить сериал
                </Button>
            </template>
        </EmptyState>
    </div>

    <div v-else class="p-6 space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Dashboard</h1>
            <p class="text-muted-foreground">Управление автоматической загрузкой сериалов</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatsCard title="Всего сериалов" :value="totalSeries" :icon="Tv" description="В списке отслеживания" />
            <StatsCard title="Активные загрузки" :value="activeDownloads" :icon="Download" :description="activeDownloads > 0 ? 'Есть активные операции' : 'Активных загрузок нет'" />
            <StatsCard title="Ожидают обновлений" :value="waitingForUpdates" :icon="Clock" description="Мониторинг RSS-лент" />
            <StatsCard title="Ошибки" :value="errorsCount" :icon="AlertCircle" description="Будут подключены через Activity" />
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:w-72">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="searchQuery" class="px-3 py-2 pl-9" placeholder="Поиск по названию..." />
            </div>
            <Select v-model="statusFilter">
                <SelectTrigger class="w-full sm:w-48">
                    <SelectValue placeholder="Все статусы" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Все статусы</SelectItem>
                    <SelectItem value="monitoring">Мониторинг</SelectItem>
                    <SelectItem value="unmonitored">Отключён</SelectItem>
                    <SelectItem value="pending">В очереди</SelectItem>
                    <SelectItem value="preparing">Подготовка</SelectItem>
                    <SelectItem value="downloading">Загрузка</SelectItem>
                    <SelectItem value="importing">Импорт</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="filteredSeries.length > 0">
            <SeriesTable :series="filteredSeries" />
        </div>
        <div v-else>
            <EmptyState :icon="Search" title="Ничего не найдено" description="Попробуйте изменить параметры поиска или фильтрации">
                <template #action>
                    <Button variant="outline" @click="resetFilters">Сбросить фильтры</Button>
                </template>
            </EmptyState>
        </div>
    </div>
</template>

<script setup>
    import { computed, onMounted, ref } from 'vue'
    import { storeToRefs } from 'pinia'
    import { Tv, PlusCircle, Download, Clock, AlertCircle, Search, RefreshCw } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
    import { Input } from '@/components/ui/input'
    import EmptyState from '@/components/EmptyState.vue'
    import StatsCard from '@/components/StatsCard.vue'
    import SeriesTable from '@/components/SeriesTable.vue'
    import useSeriesStore from '@/stores/SeriesStore'
    import { getActiveDownload, getSeriesUiStatus } from '@/domain/series'

    const seriesStore = useSeriesStore()
    const { items } = storeToRefs(seriesStore)

    const searchQuery = ref('')
    const statusFilter = ref('all')

    const totalSeries = computed(() => items.value.length)
    const activeDownloads = computed(() => items.value.filter((series) => getActiveDownload(series)).length)
    const waitingForUpdates = computed(() => items.value.filter((series) => series.monitored && ! getActiveDownload(series)).length)
    const errorsCount = ref(0)

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
        seriesStore.fetchAll().catch(() => {})
    })
</script>
