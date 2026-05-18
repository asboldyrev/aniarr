<template>
    <div class="rounded-lg border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-[300px]">Название</TableHead>
                    <TableHead>Статус</TableHead>
                    <TableHead>Прогресс</TableHead>
                    <TableHead>Формат</TableHead>
                    <TableHead>Последние серии</TableHead>
                    <TableHead>Обновлено</TableHead>
                    <TableHead class="w-[70px]"></TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow v-for="item in series">
                    <TableCell>
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-9 shrink-0 overflow-hidden rounded bg-muted">
                                <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" @error="(e) => e.target.src = '/placeholder.svg'" />
                                <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">
                                    TV
                                </div>
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
                        <StatusBadge :status="item.status" showIcon />
                    </TableCell>

                    <TableCell>
                        <div v-if="(item.status === 'downloading_avc' || item.status === 'downloading_hevc') && item.progress !== undefined" class="w-24">
                            <div class="h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-primary" :style="{ width: item.progress + '%' }"></div>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ item.progress }}%</span>
                        </div>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell>
                        <div class="flex gap-1">
                            <Badge :variant="item.hasAvc ? 'default' : 'outline'" :class="item.hasAvc ? 'bg-blue-600' : 'text-muted-foreground'">
                                AVC
                            </Badge>
                            <Badge :variant="item.hasHevc ? 'default' : 'outline'" :class="item.hasHevc ? 'bg-purple-600' : 'text-muted-foreground'">
                                HEVC
                            </Badge>
                        </div>
                    </TableCell>

                    <TableCell>
                        <span v-if="item.lastEpisodes" class="font-mono text-sm">{{ item.lastEpisodes }}</span>
                        <span v-else class="text-muted-foreground">—</span>
                    </TableCell>

                    <TableCell>
                        <span class="text-sm text-muted-foreground">
                            {{ formatDate(item.lastUpdated) }}
                        </span>
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
                                    <button class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                                        <RefreshCw class="mr-2 h-4 w-4" />
                                        Проверить RSS
                                    </button>
                                    <button class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                                        <ExternalLink class="mr-2 h-4 w-4" />
                                        TheTVDB
                                    </button>
                                    <button class="flex w-full items-center px-2 py-1.5 text-sm text-destructive hover:bg-accent rounded-sm">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Удалить
                                    </button>
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
    import { MoreHorizontal, Eye, RefreshCw, ExternalLink, Trash2 } from '@lucide/vue'

    import Table from './ui/table/Table.vue';
    import TableHeader from './ui/table/TableHeader.vue';
    import TableRow from './ui/table/TableRow.vue';
    import TableHead from './ui/table/TableHead.vue';
    import TableBody from './ui/table/TableBody.vue';
    import TableCell from './ui/table/TableCell.vue';
    import StatusBadge from '@/components/StatusBadge.vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'

    import { ref } from 'vue'
    import { RouterLink } from 'vue-router'

    const props = defineProps({
        series: {
            type: Array,
            required: true,
            default: () => []
        }
    })

    const openDropdownId = ref(null)

    const toggleDropdown = (id) => {
        openDropdownId.value = openDropdownId.value === id ? null : id
    }

    function formatDate(date) {
        // Простая заглушка: возвращаем дату как строку
        if (typeof date === 'string') return date
        if (date instanceof Date) return date.toLocaleDateString('ru-RU')
        return '—'
    }
</script>
