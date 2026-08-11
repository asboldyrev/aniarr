<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="flex items-start gap-3 sm:contents">
            <Button variant="ghost" size="icon" as-child class="shrink-0">
                <RouterLink to="/library" aria-label="Вернуться в библиотеку">
                    <ArrowLeft class="h-4 w-4" />
                </RouterLink>
            </Button>

            <div class="h-24 w-16 shrink-0 overflow-hidden rounded-lg bg-muted sm:h-40 sm:w-28">
                <img v-if="series.posterUrl" :src="series.posterUrl" :alt="series.title" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ series.title }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <span v-if="series.year">{{ series.year }}</span>
                        <span v-if="series.year && series.thetvdbId"> · </span>
                        <span v-if="series.thetvdbId">TVDB #{{ series.thetvdbId }}</span>
                    </p>
                </div>
                <StatusBadge :status="getSeriesUiStatus(series)" show-icon class="self-start" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Badge variant="outline">{{ series.seasons?.length ?? 0 }} сез.</Badge>
                <Badge v-if="hasCodec(series, 'hevc')" variant="outline" class="border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-300">HEVC</Badge>
                <Badge v-if="hasCodec(series, 'avc')" variant="outline" class="border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300">AVC</Badge>
                <Badge
                    variant="outline"
                    :class="series.sonarrId
                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                        : 'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300'"
                >
                    Sonarr {{ series.sonarrId ? `#${series.sonarrId}` : 'не подключён' }}
                </Badge>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                    <Button v-if="series.thetvdbId" variant="outline" size="sm" as-child>
                        <a :href="`https://thetvdb.com/?id=${series.thetvdbId}&tab=series`" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            TheTVDB
                        </a>
                    </Button>
                </div>

                <div class="flex items-center gap-3 rounded-lg border px-3 py-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium">Мониторинг</p>
                        <p class="text-xs text-muted-foreground">
                            {{ series.monitored ? 'RSS и автозагрузки включены' : 'RSS и автозагрузки приостановлены' }}
                        </p>
                    </div>
                    <Switch
                        :model-value="series.monitored"
                        :disabled="updatingMonitoring"
                        :aria-label="series.monitored ? 'Отключить мониторинг' : 'Включить мониторинг'"
                        @update:model-value="toggleMonitoring"
                    />
                </div>
            </div>

            <p v-if="monitoringError" class="mt-2 text-xs text-destructive">
                {{ monitoringError }}
            </p>
        </div>
    </div>
</template>

<script setup>
    import { ref } from 'vue'
    import { ArrowLeft, ExternalLink } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Switch } from '@/components/ui/switch'
    import StatusBadge from '@/components/StatusBadge.vue'
    import { getSeriesUiStatus, hasCodec } from '@/domain/series'
    import useSeriesStore from '@/stores/SeriesStore'

    const props = defineProps({
        series: { type: Object, required: true },
    })

    const seriesStore = useSeriesStore()
    const updatingMonitoring = ref(false)
    const monitoringError = ref(null)

    async function toggleMonitoring(monitored) {
        if (updatingMonitoring.value || monitored === props.series.monitored) return

        updatingMonitoring.value = true
        monitoringError.value = null

        try {
            await seriesStore.setMonitoring(props.series.id, monitored)
        } catch (exception) {
            monitoringError.value = exception?.response?.data?.message
                ?? exception?.message
                ?? 'Не удалось изменить мониторинг.'
        } finally {
            updatingMonitoring.value = false
        }
    }
</script>
