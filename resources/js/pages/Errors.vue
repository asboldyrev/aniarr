<template>
    <div v-if="errorslist.length" class="p-6 space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Ошибки</h1>
            <p class="text-muted-foreground">
                Записей с ошибками: {{ errorslist.length }}
            </p>
        </div>

        <SeriesTable :series="errorslist" />
    </div>
    <div v-else class="flex h-full items-center justify-center p-8">
        <EmptyState :icon="CheckCircle" title="Ошибок нет" description="Все сериалы работают корректно. Никаких проблем не обнаружено." iconClassName="text-green-500" />
    </div>
</template>

<script setup>
    import EmptyState from '@/components/EmptyState.vue';
    import SeriesTable from '@/components/SeriesTable.vue';

    import { CheckCircle } from '@lucide/vue';

    import { ref } from 'vue';

    const errorslist = ref([
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
</script>

<style lang="scss" scoped></style>
