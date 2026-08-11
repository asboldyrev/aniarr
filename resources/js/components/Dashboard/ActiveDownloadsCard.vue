<template>
    <Card class="gap-0 p-0">
        <CardHeader class="flex flex-row items-center justify-between gap-3 p-4 sm:p-5">
            <div>
                <CardTitle class="text-base">Активные загрузки</CardTitle>
                <CardDescription>Что Aniarr делает прямо сейчас</CardDescription>
            </div>
            <RouterLink to="/downloads" class="text-sm text-muted-foreground hover:text-foreground">
                Все
            </RouterLink>
        </CardHeader>

        <CardContent class="p-0">
            <div v-if="downloads.length === 0" class="px-4 pb-5 text-sm text-muted-foreground sm:px-5">
                Активных загрузок нет.
            </div>

            <div v-else class="divide-y">
                <RouterLink
                    v-for="item in downloads.slice(0, 5)"
                    :key="item.download.id"
                    :to="`/series/${item.series.id}`"
                    class="block px-4 py-4 transition-colors hover:bg-muted/50 sm:px-5"
                >
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ item.series.title }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                S{{ String(item.season.number).padStart(2, '0') }}
                                <span v-if="item.download.release?.codec"> · {{ item.download.release.codec.toUpperCase() }}</span>
                                · {{ statusLabel(item.download.status) }}
                            </p>
                        </div>
                        <span class="shrink-0 text-sm tabular-nums text-muted-foreground">{{ progress(item.download) }}%</span>
                    </div>

                    <Progress :model-value="progress(item.download)" class="mt-3 h-2" />
                </RouterLink>
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
    import { Progress } from '@/components/ui/progress'

    defineProps({
        downloads: { type: Array, default: () => [] },
    })

    const labels = {
        pending: 'В очереди',
        preparing: 'Подготовка',
        downloading: 'Загрузка',
        importing: 'Импорт',
    }

    function statusLabel(status) {
        return labels[status] ?? status
    }

    function progress(download) {
        return Math.max(0, Math.min(100, Number(download?.progress ?? 0)))
    }
</script>
