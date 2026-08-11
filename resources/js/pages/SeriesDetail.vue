<template>
    <div class="min-h-full">
        <div v-if="seriesStore.loading && ! series" class="flex min-h-72 items-center justify-center p-4 sm:p-6">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <div v-else-if="series" class="space-y-5 p-4 sm:space-y-6 sm:p-6">
            <SeriesHeader :series="series" />
            <SeriesStats :series="series" />

            <section class="space-y-3">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">Сезоны</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        RSS, состояние эпизодов, загрузки и история релизов
                    </p>
                </div>

                <div v-if="series.seasons?.length" class="space-y-4">
                    <SeasonCard
                        v-for="season in sortedSeasons"
                        :key="season.id"
                        :season="season"
                        @downloaded="refreshSeries"
                    />
                </div>

                <EmptyState
                    v-else
                    :icon="Layers3"
                    title="Сезоны отсутствуют"
                    description="Данные появятся после синхронизации сериала с Sonarr."
                />
            </section>
        </div>

        <div v-else class="flex min-h-72 items-center justify-center p-4 sm:p-6">
            <EmptyState
                :icon="AlertCircle"
                title="Сериал не найден"
                description="Запрашиваемый сериал не существует или не удалось загрузить данные."
            />
        </div>
    </div>
</template>

<script setup>
    import { computed, onMounted, watch } from 'vue'
    import { storeToRefs } from 'pinia'
    import { useRoute } from 'vue-router'
    import { AlertCircle, Layers3, RefreshCw } from '@lucide/vue'
    import EmptyState from '@/components/EmptyState.vue'
    import SeasonCard from '@/components/SeriesDetail/SeasonCard.vue'
    import SeriesHeader from '@/components/SeriesDetail/SeriesHeader.vue'
    import SeriesStats from '@/components/SeriesDetail/SeriesStats.vue'
    import useSeriesStore from '@/stores/SeriesStore'

    const route = useRoute()
    const seriesStore = useSeriesStore()
    const { current: series } = storeToRefs(seriesStore)

    const sortedSeasons = computed(() => [...(series.value?.seasons ?? [])]
        .sort((left, right) => left.number - right.number))

    async function refreshSeries() {
        await seriesStore.fetchOne(route.params.id).catch(() => {})
    }

    watch(() => route.params.id, (id, previousId) => {
        if (id && id !== previousId) {
            refreshSeries()
        }
    })

    onMounted(refreshSeries)
</script>
