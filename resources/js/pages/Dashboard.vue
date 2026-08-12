<template>
    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Обзор</h1>
            <p class="mt-1 text-sm text-muted-foreground">Текущее состояние библиотеки и автоматических загрузок</p>
        </div>

        <div v-if="seriesStore.loading && ! seriesStore.hasLoadedAll" class="flex min-h-72 items-center justify-center">
            <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <EmptyState
            v-else-if="seriesStore.isEmpty"
            :icon="Tv"
            title="Нет добавленных сериалов"
            description="Добавьте первый сериал через кнопку в навигации, чтобы Aniarr начал мониторинг"
        />

        <template v-else>
            <DashboardStats
                :total-series="totalSeries"
                :active-downloads="activeDownloads.length"
                :waiting-for-updates="waitingForUpdates"
                :attention-count="attentionCount"
            />

            <section class="grid gap-4 xl:grid-cols-2">
                <RecentSeriesCard :series="recentSeries" />
                <NeedsAttentionCard :items="attentionItems" />
            </section>

            <ActiveDownloadsCard :downloads="activeDownloads" />
        </template>
    </div>
</template>

<script setup>
    import { computed, onMounted, ref } from 'vue'
    import { storeToRefs } from 'pinia'
    import { RefreshCw, Tv } from '@lucide/vue'
    import ActiveDownloadsCard from '@/components/Dashboard/ActiveDownloadsCard.vue'
    import DashboardStats from '@/components/Dashboard/DashboardStats.vue'
    import NeedsAttentionCard from '@/components/Dashboard/NeedsAttentionCard.vue'
    import RecentSeriesCard from '@/components/Dashboard/RecentSeriesCard.vue'
    import EmptyState from '@/components/EmptyState.vue'
    import { getActivity } from '@/api/activity'
    import { useRealtimeRefresh } from '@/composables/useRealtimeRefresh'
    import { getActiveDownload } from '@/domain/series'
    import useSeriesStore from '@/stores/SeriesStore'

    const seriesStore = useSeriesStore()
    const { items } = storeToRefs(seriesStore)

    const attentionItems = ref([])
    const attentionCount = ref(0)

    const totalSeries = computed(() => items.value.length)

    const activeDownloads = computed(() => items.value
        .map((series) => {
            const download = getActiveDownload(series)
            if (! download) return null

            const season = (series.seasons ?? []).find((candidate) =>
                (candidate.downloads ?? []).some((item) => item.id === download.id),
            )

            return season ? { series, season, download } : null
        })
        .filter(Boolean)
        .sort((left, right) => right.download.id - left.download.id))

    const waitingForUpdates = computed(() => items.value.filter(
        (series) => series.monitored && ! getActiveDownload(series),
    ).length)

    const recentSeries = computed(() => [...items.value]
        .sort((left, right) => new Date(right.updatedAt ?? 0) - new Date(left.updatedAt ?? 0)))

    async function fetchAttention() {
        try {
            const response = await getActivity({ unresolved: 1, per_page: 5 })
            attentionItems.value = response.items
            attentionCount.value = Number(response.meta.total ?? response.items.length)
        } catch {
            attentionItems.value = []
            attentionCount.value = 0
        }
    }

    useRealtimeRefresh(
        () => seriesStore.fetchAll().catch(() => {}),
        { resources: ['series', 'download'], delay: 400 },
    )

    useRealtimeRefresh(fetchAttention, { resources: ['activity'], delay: 250 })

    onMounted(() => {
        if (! seriesStore.hasLoadedAll) {
            seriesStore.fetchAll().catch(() => {})
        }

        fetchAttention()
    })
</script>
