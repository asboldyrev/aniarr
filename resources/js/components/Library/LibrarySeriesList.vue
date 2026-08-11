<template>
    <div>
        <div class="space-y-3 md:hidden">
            <Card v-for="item in series" :key="item.id" class="gap-0 p-0">
                <CardContent class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-md bg-muted">
                            <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <RouterLink :to="`/series/${item.id}`" class="block truncate font-medium hover:underline">
                                        {{ item.title }}
                                    </RouterLink>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ item.year ?? 'Год неизвестен' }}
                                        <span v-if="lastEpisode(item)"> · {{ lastEpisode(item) }}</span>
                                    </p>
                                </div>

                                <LibrarySeriesActions :series="item" />
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <StatusBadge :status="status(item)" show-icon />
                                <Badge v-if="codecState(item) !== 'none'" variant="outline">
                                    {{ codecLabel(codecState(item)) }}
                                </Badge>
                                <Badge variant="outline" :class="rssClass(rssState(item))">
                                    {{ rssLabel(rssState(item)) }}
                                </Badge>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p class="text-muted-foreground">Эпизоды</p>
                                    <p class="mt-0.5 font-medium tabular-nums">{{ episodeStats(item).files }}/{{ episodeStats(item).total }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Обновлено</p>
                                    <p class="mt-0.5 truncate font-medium">{{ formatDate(item.updatedAt) }}</p>
                                </div>
                            </div>

                            <div v-if="activeDownload(item)" class="mt-3">
                                <div class="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{{ activeStatusLabel(activeDownload(item).status) }}</span>
                                    <span class="tabular-nums">{{ progress(activeDownload(item)) }}%</span>
                                </div>
                                <Progress :model-value="progress(activeDownload(item))" class="h-2" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="hidden overflow-hidden rounded-lg border md:block">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[320px]">Сериал</TableHead>
                        <TableHead>Эпизоды</TableHead>
                        <TableHead>Статус</TableHead>
                        <TableHead>Формат</TableHead>
                        <TableHead>RSS</TableHead>
                        <TableHead>Обновлено</TableHead>
                        <TableHead class="w-[56px]"></TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="item in series" :key="item.id">
                        <TableCell>
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="h-14 w-10 shrink-0 overflow-hidden rounded bg-muted">
                                    <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
                                </div>

                                <div class="min-w-0">
                                    <RouterLink :to="`/series/${item.id}`" class="block truncate font-medium hover:underline">
                                        {{ item.title }}
                                    </RouterLink>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ item.year ?? 'Год неизвестен' }}
                                        <span v-if="lastEpisode(item)"> · {{ lastEpisode(item) }}</span>
                                    </p>
                                </div>
                            </div>
                        </TableCell>

                        <TableCell>
                            <div class="text-sm tabular-nums">
                                <span class="font-medium">{{ episodeStats(item).files }}/{{ episodeStats(item).total }}</span>
                                <p v-if="episodeStats(item).missing > 0" class="mt-0.5 text-xs text-muted-foreground">
                                    Нет файлов: {{ episodeStats(item).missing }}
                                </p>
                            </div>
                        </TableCell>

                        <TableCell>
                            <div class="min-w-36 space-y-2">
                                <StatusBadge :status="status(item)" show-icon />
                                <div v-if="activeDownload(item)" class="w-32">
                                    <Progress :model-value="progress(activeDownload(item))" class="h-1.5" />
                                    <p class="mt-1 text-xs text-muted-foreground tabular-nums">
                                        {{ progress(activeDownload(item)) }}%
                                    </p>
                                </div>
                            </div>
                        </TableCell>

                        <TableCell>
                            <Badge variant="outline" :class="codecState(item) === 'none' ? 'text-muted-foreground' : ''">
                                {{ codecLabel(codecState(item)) }}
                            </Badge>
                        </TableCell>

                        <TableCell>
                            <Badge variant="outline" :class="rssClass(rssState(item))">
                                {{ rssLabel(rssState(item)) }}
                            </Badge>
                        </TableCell>

                        <TableCell>
                            <span class="whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(item.updatedAt) }}</span>
                        </TableCell>

                        <TableCell>
                            <LibrarySeriesActions :series="item" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

<script setup>
    import Badge from '@/components/ui/badge/Badge.vue'
    import { Card, CardContent } from '@/components/ui/card'
    import { Progress } from '@/components/ui/progress'
    import Table from '@/components/ui/table/Table.vue'
    import TableBody from '@/components/ui/table/TableBody.vue'
    import TableCell from '@/components/ui/table/TableCell.vue'
    import TableHead from '@/components/ui/table/TableHead.vue'
    import TableHeader from '@/components/ui/table/TableHeader.vue'
    import TableRow from '@/components/ui/table/TableRow.vue'
    import StatusBadge from '@/components/StatusBadge.vue'
    import LibrarySeriesActions from '@/components/Library/LibrarySeriesActions.vue'
    import {
        getActiveDownload,
        getLastEpisodeLabel,
        getSeriesCodecState,
        getSeriesEpisodeStats,
        getSeriesRssState,
        getSeriesUiStatus,
    } from '@/domain/series'

    defineProps({
        series: {
            type: Array,
            required: true,
            default: () => [],
        },
    })

    const activeStatusLabels = {
        pending: 'В очереди',
        preparing: 'Подготовка',
        downloading: 'Загрузка',
        importing: 'Импорт',
    }

    function status(series) {
        return getSeriesUiStatus(series)
    }

    function activeDownload(series) {
        return getActiveDownload(series)
    }

    function episodeStats(series) {
        return getSeriesEpisodeStats(series)
    }

    function codecState(series) {
        return getSeriesCodecState(series)
    }

    function rssState(series) {
        return getSeriesRssState(series)
    }

    function lastEpisode(series) {
        return getLastEpisodeLabel(series)
    }

    function codecLabel(codec) {
        return {
            hevc: 'HEVC',
            avc: 'AVC',
            mixed: 'AVC + HEVC',
            none: 'Нет файлов',
        }[codec] ?? codec
    }

    function rssLabel(state) {
        return {
            healthy: 'RSS активен',
            error: 'RSS ошибка',
            disabled: 'RSS отключён',
            missing: 'Нет RSS',
        }[state] ?? state
    }

    function rssClass(state) {
        if (state === 'error') return 'border-destructive/40 text-destructive'
        if (state === 'healthy') return 'text-foreground'

        return 'text-muted-foreground'
    }

    function activeStatusLabel(status) {
        return activeStatusLabels[status] ?? status
    }

    function progress(download) {
        return Math.max(0, Math.min(100, Number(download?.progress ?? 0)))
    }

    function formatDate(date) {
        if (! date) return '—'

        return new Intl.DateTimeFormat('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(new Date(date))
    }
</script>
