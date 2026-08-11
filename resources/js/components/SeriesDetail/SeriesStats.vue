<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card class="gap-0 p-0">
            <CardContent class="p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Эпизоды</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">{{ stats.files }}/{{ stats.total }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Файлов в библиотеке</p>
            </CardContent>
        </Card>

        <Card class="gap-0 p-0">
            <CardContent class="p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Не хватает</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums">{{ stats.missing }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Эпизодов без файла</p>
            </CardContent>
        </Card>

        <Card class="gap-0 p-0">
            <CardContent class="p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">RSS</p>
                <p class="mt-2 text-2xl font-semibold">{{ rssLabel }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Состояние мониторинга</p>
            </CardContent>
        </Card>

        <Card class="gap-0 p-0">
            <CardContent class="p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Sonarr</p>
                <p class="mt-2 text-2xl font-semibold">{{ series.sonarrId ? 'OK' : '—' }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ syncLabel }}</p>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import { Card, CardContent } from '@/components/ui/card'
    import { getSeriesEpisodeStats, getSeriesRssState } from '@/domain/series'

    const props = defineProps({
        series: { type: Object, required: true },
    })

    const stats = computed(() => getSeriesEpisodeStats(props.series))

    const rssLabel = computed(() => ({
        healthy: 'Активен',
        error: 'Ошибка',
        disabled: 'Отключён',
        missing: 'Нет RSS',
    }[getSeriesRssState(props.series)]))

    const syncLabel = computed(() => {
        if (! props.series.lastSonarrSyncAt) return 'Синхронизации ещё не было'
        return `Синхр.: ${new Date(props.series.lastSonarrSyncAt).toLocaleString('ru-RU')}`
    })
</script>
