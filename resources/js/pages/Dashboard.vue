<template>
    <div v-if="isEmpty" class="flex h-full items-center justify-center p-8">
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
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold">Dashboard</h1>
            <p class="text-muted-foreground">Управление автоматической загрузкой сериалов</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatsCard title="Всего сериалов" :value="totalSeries" :icon="Tv" description="В списке отслеживания" />
            <StatsCard title="Активные загрузки" :value="activeDownloads" :icon="Download" :description="activeDownloads > 0 ? 'Торренты качаются' : 'Все загрузки завершены'" />
            <StatsCard title="Ожидают обновлений" :value="waitingForUpdates" :icon="Clock" description="Мониторинг RSS-лент" />
            <StatsCard title="Ошибки" :value="errorsCount" :icon="AlertCircle" :description="errorsCount > 0 ? 'Требуют внимания' : 'Проблем нет'" />
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:w-72">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input class="px-3 py-2 pl-9" placeholder="Поиск по названию..." />
            </div>
            <Select :value="statusFilter" @update:modelValue="console.log(111)">
                <SelectTrigger class="w-full sm:w-48">
                    <SelectValue placeholder="Все статусы" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Все статусы</SelectItem>
                    <SelectItem value="waiting">Ожидание</SelectItem>
                    <SelectItem value="new_episodes">Новые серии</SelectItem>
                    <SelectItem value="downloading_avc">Загрузка AVC</SelectItem>
                    <SelectItem value="downloading_hevc">Загрузка HEVC</SelectItem>
                    <SelectItem value="processing_sonarr">Обработка Sonarr</SelectItem>
                    <SelectItem value="syncing_jellyfin">Синхронизация Jellyfin</SelectItem>
                    <SelectItem value="done">Готово</SelectItem>
                    <SelectItem value="error">Ошибка</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Series Table -->
        <div v-if="filteredSeries.length > 0">
            <SeriesTable :series="filteredSeries" />
        </div>
        <div v-else>
            <EmptyState :icon="Search" title="Ничего не найдено" description="Попробуйте изменить параметры поиска или фильтрации">
                <template #action>
                    <Button variant="outline" @click="resetFilters">
                        Сбросить фильтры
                    </Button>
                </template>
            </EmptyState>
        </div>
    </div>
</template>

<script setup>
    import { Tv, PlusCircle, Download, Clock, AlertCircle, Search } from '@lucide/vue';

    import Button from '@/components/ui/button/Button.vue';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Input } from '@/components/ui/input';

    import EmptyState from '@/components/EmptyState.vue';
    import StatsCard from '@/components/StatsCard.vue';
    import SeriesTable from '@/components/SeriesTable.vue';

    import { ref } from 'vue';

    const isEmpty = ref(false)

    const totalSeries = ref(2)
    const activeDownloads = ref(5)
    const waitingForUpdates = ref(0)
    const errorsCount = ref(1)

    const statusFilter = ref('all')
    const searchQuery = ref('')

    const filteredSeries = ref([
        {
            id: 1,
            title: 'Breaking Bad',
            year: 2008,
            posterUrl: 'https://image.tmdb.org/t/p/w200/ggFHVNu6YYI5L9pCfOacjizRGt.jpg',
            status: 'waiting',
            progress: undefined,
            hasAvc: true,
            hasHevc: false,
            lastEpisodes: 'S05E14',
            lastUpdated: new Date('2024-01-15T10:30:00')
        },
        {
            id: 2,
            title: 'Game of Thrones',
            year: 2011,
            posterUrl: '',
            status: 'new_episodes',
            progress: undefined,
            hasAvc: true,
            hasHevc: true,
            lastEpisodes: 'S08E06',
            lastUpdated: new Date('2024-01-14T18:45:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'downloading_avc',
            progress: 65,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'processing_sonarr',
            progress: undefined,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'downloading_hevc',
            progress: 78,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'syncing_jellyfin',
            progress: undefined,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'done',
            progress: undefined,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        },
        {
            id: 3,
            title: 'Stranger Things',
            year: 2016,
            posterUrl: 'https://image.tmdb.org/t/p/w200/49WJfeN0moxb9IPfGn8AIqMGskD.jpg',
            status: 'error',
            progress: undefined,
            hasAvc: false,
            hasHevc: true,
            lastEpisodes: 'S04E09',
            lastUpdated: new Date('2024-01-13T22:10:00')
        }
    ])

    const resetFilters = () => {
        searchQuery.value = ''
        statusFilter.value = 'all'
    }
</script>

<style lang="scss" scoped></style>
