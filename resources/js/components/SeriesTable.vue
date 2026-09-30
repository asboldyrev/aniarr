<template>
    <div>
        <div class="space-y-3 md:hidden">
            <Card v-for="item in series" :key="item.id" class="gap-0 p-0">
                <CardContent class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-muted">
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
                                        <span v-if="getLastEpisodeLabel(item)"> · {{ getLastEpisodeLabel(item) }}</span>
                                    </p>
                                </div>

                                <SeriesActions :series="item" />
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <StatusBadge :status="getSeriesUiStatus(item)" showIcon />
                                <Badge v-if="hasCodec(item, 'avc')" variant="secondary">AVC</Badge>
                                <Badge v-if="hasCodec(item, 'hevc')" variant="secondary">HEVC</Badge>
                            </div>

                            <div v-if="getActiveDownload(item)" class="mt-3">
                                <div class="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Прогресс</span>
                                    <span>{{ progress(item) }}%</span>
                                </div>
                                <Progress :model-value="progress(item)" class="h-2" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="hidden rounded-lg border md:block">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[300px]">Название</TableHead>
                        <TableHead>Статус</TableHead>
                        <TableHead>Прогресс</TableHead>
                        <TableHead>Формат</TableHead>
                        <TableHead>Последняя серия</TableHead>
                        <TableHead>Обновлено</TableHead>
                        <TableHead class="w-[70px]"></TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="item in series" :key="item.id">
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-9 shrink-0 overflow-hidden rounded bg-muted">
                                    <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
                                </div>
                                <div class="min-w-0">
                                    <RouterLink :to="`/series/${item.id}`" class="block truncate font-medium hover:underline">
                                        {{ item.title }}
                                    </RouterLink>
                                    <span v-if="item.year" class="text-xs text-muted-foreground">{{ item.year }}</span>
                                </div>
                            </div>
                        </TableCell>

                        <TableCell>
                            <StatusBadge :status="getSeriesUiStatus(item)" showIcon />
                        </TableCell>

                        <TableCell>
                            <div v-if="getActiveDownload(item)" class="w-24">
                                <Progress :model-value="progress(item)" class="h-2" />
                                <span class="text-xs text-muted-foreground">{{ progress(item) }}%</span>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>

                        <TableCell>
                            <div class="flex gap-1">
                                <Badge :variant="hasCodec(item, 'avc') ? 'secondary' : 'outline'">AVC</Badge>
                                <Badge :variant="hasCodec(item, 'hevc') ? 'secondary' : 'outline'">HEVC</Badge>
                            </div>
                        </TableCell>

                        <TableCell>
                            <span v-if="getLastEpisodeLabel(item)" class="font-mono text-sm">{{ getLastEpisodeLabel(item) }}</span>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>

                        <TableCell>
                            <span class="text-sm text-muted-foreground">{{ formatDate(item.updatedAt) }}</span>
                        </TableCell>

                        <TableCell>
                            <SeriesActions :series="item" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

<script setup>
    import { ExternalLink, Eye, MoreHorizontal } from '@lucide/vue'
    import { h } from 'vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Card, CardContent } from '@/components/ui/card'
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu'
    import { Progress } from '@/components/ui/progress'
    import Table from '@/components/ui/table/Table.vue'
    import TableBody from '@/components/ui/table/TableBody.vue'
    import TableCell from '@/components/ui/table/TableCell.vue'
    import TableHead from '@/components/ui/table/TableHead.vue'
    import TableHeader from '@/components/ui/table/TableHeader.vue'
    import TableRow from '@/components/ui/table/TableRow.vue'
    import StatusBadge from '@/components/StatusBadge.vue'
    import { getActiveDownload, getLastEpisodeLabel, getSeriesUiStatus, hasCodec } from '@/domain/series'

    defineProps({
        series: {
            type: Array,
            required: true,
            default: () => [],
        },
    })

    const SeriesActions = (props) => h(DropdownMenu, null, {
        default: () => [
            h(DropdownMenuTrigger, { asChild: true }, {
                default: () => h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8' }, {
                    default: () => h(MoreHorizontal, { class: 'h-4 w-4' }),
                }),
            }),
            h(DropdownMenuContent, { align: 'end', class: 'w-44' }, {
                default: () => [
                    h(DropdownMenuItem, { asChild: true }, {
                        default: () => h('a', { href: `/series/${props.series.id}`, class: 'flex items-center gap-2' }, [
                            h(Eye, { class: 'h-4 w-4' }),
                            'Подробнее',
                        ]),
                    }),
                    h(DropdownMenuItem, { asChild: true }, {
                        default: () => h('a', {
                            href: `https://thetvdb.com/?id=${props.series.thetvdbId}&tab=series`,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            class: 'flex items-center gap-2',
                        }, [
                            h(ExternalLink, { class: 'h-4 w-4' }),
                            'TheTVDB',
                        ]),
                    }),
                ],
            }),
        ],
    })

    function progress(series) {
        return Math.max(0, Math.min(100, Number(getActiveDownload(series)?.progress ?? 0)))
    }

    function formatDate(date) {
        if (! date) return '—'
        return new Date(date).toLocaleString('ru-RU')
    }
</script>
