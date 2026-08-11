<template>
    <Card class="gap-0 p-0">
        <CardHeader class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <CardTitle class="text-lg">Сезон {{ season.number }}</CardTitle>
                    <CardDescription>
                        {{ season.filesCount }} из {{ season.episodesCount }} эпизодов в библиотеке
                    </CardDescription>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Badge
                        variant="outline"
                        :class="effectiveMonitored
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                            : 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'"
                    >
                        {{ effectiveMonitored ? 'Мониторинг' : 'Приостановлен' }}
                    </Badge>
                    <Badge
                        v-if="missingCount > 0"
                        variant="outline"
                        class="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300"
                    >
                        Нет {{ missingCount }}
                    </Badge>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 p-4 pt-0 sm:p-5 sm:pt-0">
            <div class="grid gap-3 lg:grid-cols-2">
                <SeasonRssStatus
                    :feed="season.rssFeed"
                    :monitored="effectiveMonitored"
                    :season-id="season.id"
                    :season-number="season.number"
                    @changed="$emit('changed')"
                />
                <SeasonDownload :download="season.activeDownload" />
            </div>

            <EpisodeList :episodes="season.episodes ?? []" />
            <ReleaseList
                :releases="season.rssFeed?.releases ?? []"
                :episodes="season.episodes ?? []"
                :downloads="season.downloads ?? []"
                :has-active-download="Boolean(season.activeDownload)"
                @downloaded="$emit('downloaded')"
            />
        </CardContent>
    </Card>
</template>

<script setup>
    import { computed } from 'vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
    import EpisodeList from '@/components/SeriesDetail/EpisodeList.vue'
    import ReleaseList from '@/components/SeriesDetail/ReleaseList.vue'
    import SeasonDownload from '@/components/SeriesDetail/SeasonDownload.vue'
    import SeasonRssStatus from '@/components/SeriesDetail/SeasonRssStatus.vue'

    const props = defineProps({
        season: { type: Object, required: true },
        seriesMonitored: { type: Boolean, default: true },
    })

    defineEmits(['downloaded', 'changed'])

    const effectiveMonitored = computed(() => props.seriesMonitored && props.season.monitored)
    const missingCount = computed(() => Math.max(0, Number(props.season.episodesCount ?? 0) - Number(props.season.filesCount ?? 0)))
</script>
