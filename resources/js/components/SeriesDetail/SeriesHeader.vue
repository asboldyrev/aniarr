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
                <Badge v-if="hasCodec(series, 'hevc')" variant="secondary">HEVC</Badge>
                <Badge v-if="hasCodec(series, 'avc')" variant="secondary">AVC</Badge>
                <Badge :variant="series.sonarrId ? 'secondary' : 'outline'">
                    Sonarr {{ series.sonarrId ? `#${series.sonarrId}` : 'не подключён' }}
                </Badge>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Button v-if="series.thetvdbId" variant="outline" size="sm" as-child>
                    <a :href="`https://thetvdb.com/?id=${series.thetvdbId}&tab=series`" target="_blank" rel="noopener noreferrer">
                        <ExternalLink class="mr-2 h-4 w-4" />
                        TheTVDB
                    </a>
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ArrowLeft, ExternalLink } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import StatusBadge from '@/components/StatusBadge.vue'
    import { getSeriesUiStatus, hasCodec } from '@/domain/series'

    defineProps({
        series: { type: Object, required: true },
    })
</script>
