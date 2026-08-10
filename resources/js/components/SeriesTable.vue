<template>
    <div class="rounded-lg border">
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
                                <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" @error="(e) => e.target.src = '/placeholder.svg'" />
                                <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
                            </div>
                            <div class="min-w-0">
                                <router-link :to="`/series/${item.id}`" class="font-medium hover:underline truncate block">
                                    {{ item.title }}
                                </router-link>
                                <span v-if="item.year" class="text-xs text-muted-foreground">{{ item.year }}</span>
                            </div>
                        </div>
                    </TableCell>

                    <TableCell>
                        <StatusBadge :status="getSeriesUiStatus(item)" showIcon />
                    </TableCell>

                    <TableCell>
                        <div v-if="getActiveDownload(item)?.progress !== null && getActiveDownload(item)?.progress !== undefined" class="w-24">
                            <div class="h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary" :style="{ width: `${getActiveDownload(item).progress}%` }"></div>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ getActiveDownload(item).progress }}%</span>
                        </div>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell>
                        <div class="flex gap-1">
                            <Badge :variant="hasCodec(item, 'avc') ? 'default' : 'outline'" :class="hasCodec(item, 'avc') ? 'bg-blue-600' : 'text-muted-foreground'">AVC</Badge>
                            <Badge :variant="hasCodec(item, 'hevc') ? 'default' : 'outline'" :class="hasCodec(item, 'hevc') ? 'bg-purple-600' : 'text-muted-foreground'">HEVC</Badge>
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
                        <div class="relative">
                            <Button variant="ghost" size="icon" class="h-8 w-8" @click="toggleDropdown(item.id)">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                            <div v-if="openDropdownId === item.id" class="absolute right-0 top-full z-10 mt-1 w-48 rounded-md border bg-popover shadow-md">
                                <div class="p-1">
                                    <router-link :to="`/series/${item.id}`" class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                                        <Eye class="mr-2 h-4 w-4" />
                                        Подробнее
                                    </router-link>
                                    <a :href="`https://thetvdb.com/?id=${item.thetvdbId}&tab=series`" target="_blank" rel="noopener noreferrer" class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                                        <ExternalLink class="mr-2 h-4 w-4" />
                                        TheTVDB
                                    </a>
                                </div>
                            </div>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>

<script setup>
    import { MoreHorizontal, Eye, ExternalLink } from '@lucide/vue'
    import { ref } from 'vue'
    import Table from './ui/table/Table.vue'
    import TableHeader from './ui/table/TableHeader.vue'
    import TableRow from './ui/table/TableRow.vue'
    import TableHead from './ui/table/TableHead.vue'
    import TableBody from './ui/table/TableBody.vue'
    import TableCell from './ui/table/TableCell.vue'
    import StatusBadge from '@/components/StatusBadge.vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { getActiveDownload, getLastEpisodeLabel, getSeriesUiStatus, hasCodec } from '@/domain/series'

    defineProps({
        series: {
            type: Array,
            required: true,
            default: () => [],
        },
    })

    const openDropdownId = ref(null)

    const toggleDropdown = (id) => {
        openDropdownId.value = openDropdownId.value === id ? null : id
    }

    function formatDate(date) {
        if (! date) return '—'

        return new Date(date).toLocaleString('ru-RU')
    }
</script>
