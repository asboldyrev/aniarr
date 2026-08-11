<template>
    <Card class="gap-0 p-0">
        <CardContent class="p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 gap-3">
                    <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-muted">
                        <img v-if="download.series?.posterUrl" :src="download.series.posterUrl" :alt="download.series.title" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">TV</div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <RouterLink
                                v-if="download.series"
                                :to="`/series/${download.series.id}`"
                                class="truncate font-medium hover:underline"
                            >
                                {{ download.series.title }}
                            </RouterLink>
                            <span v-else class="font-medium">Download #{{ download.id }}</span>
                            <Badge variant="outline">S{{ String(download.season?.number ?? 0).padStart(2, '0') }}</Badge>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <StatusBadge :status="download.status" show-icon />
                            <Badge v-if="download.release?.codec" variant="secondary">{{ download.release.codec.toUpperCase() }}</Badge>
                            <Badge variant="outline">{{ triggerLabel }}</Badge>
                            <Badge v-for="reason in reasons" :key="reason" variant="outline">{{ reasonLabel(reason) }}</Badge>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Button v-if="hasDetails" variant="outline" size="sm" @click="detailsOpen = true">
                        Детали
                    </Button>
                    <span class="text-xs text-muted-foreground">#{{ download.id }}</span>
                </div>
            </div>

            <div v-if="isActive" class="mt-4">
                <div class="mb-1 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                    <span>{{ activeLabel }}</span>
                    <span class="shrink-0 tabular-nums">{{ progress }}%</span>
                </div>
                <Progress :model-value="progress" class="h-2" />
                <p v-if="download.etaSeconds !== null && download.etaSeconds !== undefined" class="mt-1 text-xs text-muted-foreground">
                    ETA: {{ formatEta(download.etaSeconds) }}
                </p>
            </div>

            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs text-muted-foreground">Релиз</p>
                    <p class="mt-0.5 truncate">{{ releaseLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Эпизоды</p>
                    <p class="mt-0.5">{{ episodesLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Начало</p>
                    <p class="mt-0.5">{{ formatDate(download.startedAt ?? download.queuedAt) }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Завершение</p>
                    <p class="mt-0.5">{{ formatDate(download.completedAt ?? download.failedAt) }}</p>
                </div>
            </div>

            <div v-if="download.errorMessage" class="mt-4 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                {{ download.errorMessage }}
            </div>
        </CardContent>
    </Card>

    <Dialog v-model:open="detailsOpen">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Download #{{ download.id }}</DialogTitle>
                <DialogDescription>{{ download.series?.title ?? 'Aniarr Download' }}</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium">Файлы</h4>
                    <div v-if="download.items?.length" class="mt-2 divide-y rounded-md border">
                        <div v-for="item in download.items" :key="item.id" class="p-3 text-sm">
                            <p class="break-all font-medium">{{ item.torrentFileName ?? `Episode #${item.episodeId}` }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ reasonLabel(item.reason) }}
                                <span v-if="item.episode?.episodeNumber !== undefined"> · E{{ String(item.episode.episodeNumber).padStart(2, '0') }}</span>
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-2 text-sm text-muted-foreground">Файлы не сохранены.</p>
                </div>

                <div v-if="download.qbitHash">
                    <h4 class="text-sm font-medium">qBittorrent hash</h4>
                    <code class="mt-2 block break-all rounded-md bg-muted p-3 text-xs">{{ download.qbitHash }}</code>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Card, CardContent } from '@/components/ui/card'
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
    import { Progress } from '@/components/ui/progress'
    import StatusBadge from '@/components/StatusBadge.vue'

    const props = defineProps({
        download: { type: Object, required: true },
    })

    const detailsOpen = ref(false)
    const activeStatuses = new Set(['pending', 'preparing', 'downloading', 'importing'])

    const isActive = computed(() => activeStatuses.has(props.download.status))
    const progress = computed(() => Math.max(0, Math.min(100, Number(props.download.progress ?? 0))))
    const hasDetails = computed(() => (props.download.items?.length ?? 0) > 0 || Boolean(props.download.qbitHash))
    const reasons = computed(() => [...new Set((props.download.items ?? []).map((item) => item.reason).filter(Boolean))])
    const triggerLabel = computed(() => props.download.trigger === 'manual' ? 'Ручной' : 'Автоматический')
    const activeLabel = computed(() => ({
        pending: 'Ожидание запуска',
        preparing: 'Подготовка torrent',
        downloading: 'Загрузка',
        importing: 'Импорт в Sonarr',
    })[props.download.status] ?? 'Выполняется')

    const releaseLabel = computed(() => {
        const release = props.download.release
        if (! release) return '—'

        const range = release.firstEpisode && release.lastEpisode
            ? `E${String(release.firstEpisode).padStart(2, '0')}–E${String(release.lastEpisode).padStart(2, '0')}`
            : null

        return [release.quality, range].filter(Boolean).join(' · ') || release.title || '—'
    })

    const episodesLabel = computed(() => {
        const items = props.download.items ?? []
        if (items.length === 0) return '—'

        const numbers = items
            .map((item) => item.episode?.episodeNumber)
            .filter((number) => number !== null && number !== undefined)
            .sort((a, b) => a - b)

        if (numbers.length === 0) return `${items.length} файл(ов)`
        if (numbers.length === 1) return `E${String(numbers[0]).padStart(2, '0')}`

        return `E${String(numbers[0]).padStart(2, '0')}–E${String(numbers[numbers.length - 1]).padStart(2, '0')} · ${items.length}`
    })

    function reasonLabel(reason) {
        return {
            missing: 'Недостающие',
            upgrade: 'Upgrade',
            refresh: 'Повторная загрузка',
        }[reason] ?? reason
    }

    function formatDate(value) {
        if (! value) return '—'
        return new Date(value).toLocaleString('ru-RU')
    }

    function formatEta(seconds) {
        const value = Math.max(0, Number(seconds ?? 0))
        const hours = Math.floor(value / 3600)
        const minutes = Math.floor((value % 3600) / 60)
        const remainingSeconds = Math.floor(value % 60)

        return [hours, minutes, remainingSeconds]
            .map((part) => String(part).padStart(2, '0'))
            .join(':')
    }
</script>
