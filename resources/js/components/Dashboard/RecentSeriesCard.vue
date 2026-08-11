<template>
    <Card class="gap-0 p-0">
        <CardHeader class="flex flex-row items-center justify-between gap-3 p-4 sm:p-5">
            <div>
                <CardTitle class="text-base">Недавно обновлённые</CardTitle>
                <CardDescription>Последние изменения в библиотеке</CardDescription>
            </div>
            <RouterLink to="/library" class="text-sm text-muted-foreground hover:text-foreground">
                Библиотека
            </RouterLink>
        </CardHeader>

        <CardContent class="p-0">
            <div v-if="series.length === 0" class="px-4 pb-5 text-sm text-muted-foreground sm:px-5">
                Сериалов пока нет.
            </div>

            <div v-else class="divide-y">
                <RouterLink
                    v-for="item in series.slice(0, 6)"
                    :key="item.id"
                    :to="`/series/${item.id}`"
                    class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/50 sm:px-5"
                >
                    <div class="h-12 w-9 shrink-0 overflow-hidden rounded bg-muted">
                        <img v-if="item.posterUrl" :src="item.posterUrl" :alt="item.title" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-muted-foreground">TV</div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ item.title }}</p>
                        <p class="mt-0.5 truncate text-xs text-muted-foreground">
                            {{ item.year ?? 'Год неизвестен' }}
                            <span v-if="lastEpisode(item)"> · {{ lastEpisode(item) }}</span>
                        </p>
                    </div>

                    <StatusBadge :status="status(item)" class="hidden shrink-0 sm:inline-flex" />
                </RouterLink>
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
    import StatusBadge from '@/components/StatusBadge.vue'
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
    import { getLastEpisodeLabel, getSeriesUiStatus } from '@/domain/series'

    defineProps({
        series: { type: Array, default: () => [] },
    })

    const status = (series) => getSeriesUiStatus(series)
    const lastEpisode = (series) => getLastEpisodeLabel(series)
</script>
