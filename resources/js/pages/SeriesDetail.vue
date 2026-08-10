<template>
    <div v-if="seriesStore.loading" class="flex h-full items-center justify-center p-8">
        <RefreshCw class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="series" class="p-4 md:p-6 space-y-6 overflow-x-hidden">
        <div class="flex items-start gap-4">
            <Button variant="ghost" size="icon" asChild>
                <RouterLink to="/">
                    <ArrowLeft class="h-4 w-4" />
                </RouterLink>
            </Button>

            <div class="flex flex-1 gap-6">
                <div class="hidden sm:block h-40 w-28 shrink-0 overflow-hidden rounded-lg bg-muted">
                    <img v-if="series.posterUrl" :src="series.posterUrl" :alt="series.title" class="h-full w-full object-cover" />
                    <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">TV</div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">{{ series.title }}</h1>
                            <p v-if="series.year" class="text-muted-foreground">{{ series.year }}</p>
                        </div>
                        <StatusBadge showIcon :status="getSeriesUiStatus(series)" />
                    </div>

                    <div v-if="activeDownload" class="mt-4 max-w-sm">
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ downloadLabel(activeDownload.status) }}</span>
                            <span v-if="activeDownload.progress !== null">{{ activeDownload.progress }}%</span>
                        </div>
                        <Progress
                            v-if="activeDownload.progress !== null"
                            class="relative h-4 w-full overflow-hidden rounded-full bg-secondary"
                            :model-value="activeDownload.progress"
                        />
                        <p v-if="activeDownload.errorMessage" class="mt-2 text-sm text-destructive">
                            {{ activeDownload.errorMessage }}
                        </p>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button v-for="season in seasonsWithFeed" :key="season.id" variant="outline" size="sm" class="gap-1.5 text-xs md:text-sm md:gap-2" asChild>
                            <a :href="season.rssFeed.rssUrl" target="_blank" rel="noopener noreferrer">
                                <Rss class="h-3 w-3" />
                                RSS S{{ season.number }}
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs md:text-sm md:gap-2" asChild>
                            <a :href="`https://thetvdb.com/?id=${series.thetvdbId}&tab=series`" target="_blank" rel="noopener noreferrer">
                                <ExternalLink class="h-3 w-3" />
                                TheTVDB
                            </a>
                        </Button>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <Badge :variant="hasCodec(series, 'avc') ? 'default' : 'outline'" :class="hasCodec(series, 'avc') ? 'bg-blue-600' : ''">
                            AVC {{ hasCodec(series, 'avc') ? '✓' : '✗' }}
                        </Badge>
                        <Badge :variant="hasCodec(series, 'hevc') ? 'default' : 'outline'" :class="hasCodec(series, 'hevc') ? 'bg-purple-600' : ''">
                            HEVC {{ hasCodec(series, 'hevc') ? '✓' : '✗' }}
                        </Badge>
                        <Badge :variant="series.sonarrId ? 'default' : 'outline'" :class="series.sonarrId ? 'bg-green-600' : ''">
                            Sonarr {{ series.sonarrId ? '✓' : '✗' }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="flex items-center gap-2 text-2xl font-semibold leading-none tracking-tight">
                        <Rss class="h-5 w-5" />
                        Сезоны и RSS
                    </CardTitle>
                    <CardDescription>Состояние мониторинга по сезонам</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0">
                    <div class="space-y-3">
                        <div v-for="season in series.seasons" :key="season.id" class="rounded-lg border p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium">Сезон {{ season.number }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ season.filesCount }} / {{ season.episodesCount }} файлов
                                    </p>
                                </div>
                                <Badge variant="outline">{{ season.monitored ? 'Мониторинг' : 'Отключён' }}</Badge>
                            </div>
                            <div v-if="season.rssFeed" class="mt-3 text-sm text-muted-foreground">
                                <p>{{ season.rssFeed.enabled ? 'RSS включён' : 'RSS отключён' }}</p>
                                <p v-if="season.rssFeed.lastRssCheck">Проверен: {{ formatDate(season.rssFeed.lastRssCheck) }}</p>
                                <p v-if="season.rssFeed.lastError" class="text-destructive">{{ season.rssFeed.lastError }}</p>
                            </div>
                            <p v-else class="mt-3 text-sm text-muted-foreground">RSS не настроен</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="flex items-center gap-2 text-2xl font-semibold leading-none tracking-tight">
                        <Film class="h-5 w-5" />
                        Эпизоды
                    </CardTitle>
                    <CardDescription>Фактическое состояние файлов из Sonarr</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0">
                    <div v-if="episodes.length" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="whitespace-nowrap">Серия</TableHead>
                                    <TableHead>Файл</TableHead>
                                    <TableHead>Codec</TableHead>
                                    <TableHead class="whitespace-nowrap">Добавлен</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="episode in episodes" :key="episode.id">
                                    <TableCell class="font-mono text-xs md:text-sm">
                                        S{{ String(episode.seasonNumber).padStart(2, '0') }}E{{ String(episode.episodeNumber).padStart(2, '0') }}
                                    </TableCell>
                                    <TableCell>
                                        <CheckCircle v-if="episode.hasFile" class="h-4 w-4 text-green-500" />
                                        <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="episode.fileCodec" variant="outline" class="uppercase">{{ episode.fileCodec }}</Badge>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground text-xs md:text-sm whitespace-nowrap">
                                        {{ formatDate(episode.fileDateAdded) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <EmptyState v-else :icon="Film" title="Эпизоды отсутствуют" description="Данные появятся после синхронизации с Sonarr" />
                </CardContent>
            </Card>
        </div>
    </div>

    <div v-else class="flex h-full items-center justify-center p-8">
        <EmptyState :icon="AlertCircle" title="Сериал не найден" description="Запрашиваемый сериал не существует или не удалось загрузить данные" />
    </div>
</template>

<script setup>
    import { computed, onMounted } from 'vue'
    import { storeToRefs } from 'pinia'
    import { useRoute } from 'vue-router'
    import EmptyState from '@/components/EmptyState.vue'
    import StatusBadge from '@/components/StatusBadge.vue'
    import Progress from '@/components/ui/progress/Progress.vue'
    import Button from '@/components/ui/button/Button.vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
    import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
    import { AlertCircle, ArrowLeft, Rss, ExternalLink, RefreshCw, CheckCircle, XCircle, Film } from '@lucide/vue'
    import useSeriesStore from '@/stores/SeriesStore'
    import { getActiveDownload, getSeriesUiStatus, hasCodec } from '@/domain/series'

    const route = useRoute()
    const seriesStore = useSeriesStore()
    const { current: series } = storeToRefs(seriesStore)

    const activeDownload = computed(() => getActiveDownload(series.value))
    const seasonsWithFeed = computed(() => (series.value?.seasons ?? []).filter((season) => season.rssFeed))
    const episodes = computed(() => (series.value?.seasons ?? []).flatMap((season) =>
        (season.episodes ?? []).map((episode) => ({
            ...episode,
            seasonNumber: season.number,
        })),
    ))

    function downloadLabel(status) {
        return {
            pending: 'В очереди',
            preparing: 'Подготовка загрузки',
            downloading: 'Загрузка',
            importing: 'Импорт в Sonarr',
        }[status] ?? status
    }

    function formatDate(date) {
        if (! date) return '—'

        return new Date(date).toLocaleString('ru-RU')
    }

    onMounted(() => {
        seriesStore.fetchOne(route.params.id).catch(() => {})
    })
</script>
