<template>
    <div class="rounded-lg border">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 p-4 text-left"
            @click="open = ! open"
        >
            <div>
                <p class="text-sm font-medium">Релизы</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ releases.length }} в истории RSS</p>
            </div>
            <ChevronDown class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" />
        </button>

        <div v-if="open" class="border-t">
            <div v-if="releases.length === 0" class="p-4 text-sm text-muted-foreground">
                Релизы пока не найдены.
            </div>

            <div v-else class="divide-y">
                <div v-for="release in sortedReleases" :key="release.id" class="p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    class="uppercase"
                                    :class="release.codec === 'hevc'
                                        ? 'border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-300'
                                        : 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300'"
                                >
                                    {{ release.codec }}
                                </Badge>
                                <Badge v-if="release.quality" variant="outline">{{ release.quality }}</Badge>

                                <Badge
                                    v-if="release.isCurrent"
                                    variant="outline"
                                    class="border-cyan-500/30 bg-cyan-500/10 text-cyan-700 dark:text-cyan-300"
                                >
                                    Актуальный RSS
                                </Badge>

                                <Badge
                                    v-if="downloadState(release) === 'active'"
                                    variant="outline"
                                    class="border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300"
                                >
                                    Загружается
                                </Badge>
                                <Badge
                                    v-else-if="downloadState(release) === 'completed'"
                                    variant="outline"
                                    class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                >
                                    Скачан
                                </Badge>
                                <Badge
                                    v-else-if="downloadState(release) === 'failed'"
                                    variant="outline"
                                    class="border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300"
                                >
                                    Ошибка загрузки
                                </Badge>

                                <Badge
                                    v-if="! release.isCurrent && downloadState(release) === null"
                                    variant="outline"
                                    class="border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300"
                                >
                                    История
                                </Badge>
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm font-medium">{{ release.title }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                E{{ release.firstEpisode }}–E{{ release.lastEpisode }}
                                <span v-if="release.publishedAt"> · {{ formatDate(release.publishedAt) }}</span>
                                <span v-if="release.sizeBytes"> · {{ formatBytes(release.sizeBytes) }}</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Button v-if="release.torrentUrl" variant="outline" size="sm" as-child>
                                <a :href="release.torrentUrl" target="_blank" rel="noopener noreferrer" aria-label="Открыть torrent">
                                    <ExternalLink class="h-4 w-4" />
                                </a>
                            </Button>
                            <ReleaseDownloadDialog
                                :release="release"
                                :episodes="episodes"
                                :disabled="hasActiveDownload"
                                @downloaded="$emit('downloaded')"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import { ChevronDown, ExternalLink } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import ReleaseDownloadDialog from '@/components/SeriesDetail/ReleaseDownloadDialog.vue'

    const ACTIVE_DOWNLOAD_STATUSES = new Set(['pending', 'preparing', 'downloading', 'importing'])

    const props = defineProps({
        releases: { type: Array, default: () => [] },
        episodes: { type: Array, default: () => [] },
        downloads: { type: Array, default: () => [] },
        hasActiveDownload: { type: Boolean, default: false },
    })

    defineEmits(['downloaded'])

    const open = ref(false)

    const downloadsByRelease = computed(() => {
        const map = new Map()

        for (const download of props.downloads) {
            const releaseId = Number(download.releaseId)
            const current = map.get(releaseId)

            if (! current || Number(download.id) > Number(current.id)) {
                map.set(releaseId, download)
            }
        }

        return map
    })

    const sortedReleases = computed(() => [...props.releases].sort((left, right) => {
        const leftState = downloadState(left)
        const rightState = downloadState(right)

        if (leftState === 'active' && rightState !== 'active') return -1
        if (rightState === 'active' && leftState !== 'active') return 1
        if (left.isCurrent !== right.isCurrent) return left.isCurrent ? -1 : 1

        return new Date(right.publishedAt ?? 0) - new Date(left.publishedAt ?? 0)
    }))

    function downloadState(release) {
        const download = downloadsByRelease.value.get(Number(release.id))
        if (! download) return null

        if (ACTIVE_DOWNLOAD_STATUSES.has(download.status)) return 'active'
        if (download.status === 'completed') return 'completed'
        if (download.status === 'failed') return 'failed'

        return null
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('ru-RU')
    }

    function formatBytes(bytes) {
        const value = Number(bytes ?? 0)
        if (value >= 1024 ** 3) return `${(value / 1024 ** 3).toFixed(1)} GB`
        if (value >= 1024 ** 2) return `${(value / 1024 ** 2).toFixed(0)} MB`
        return `${Math.round(value / 1024)} KB`
    }
</script>
