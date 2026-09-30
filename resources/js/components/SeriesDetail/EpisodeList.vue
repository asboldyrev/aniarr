<template>
    <div class="rounded-lg border">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 p-4 text-left"
            @click="open = ! open"
        >
            <div>
                <p class="text-sm font-medium">Эпизоды</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ filesCount }}/{{ episodes.length }} файлов</p>
            </div>
            <ChevronDown class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" />
        </button>

        <div v-if="open" class="border-t">
            <div v-if="episodes.length === 0" class="p-4 text-sm text-muted-foreground">
                Эпизоды пока не синхронизированы.
            </div>
            <div v-else class="divide-y">
                <div v-for="episode in episodes" :key="episode.id" class="flex items-center gap-3 px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted font-mono text-xs">
                        E{{ String(episode.episodeNumber).padStart(2, '0') }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ episode.title || `Эпизод ${episode.episodeNumber}` }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ episode.hasFile ? 'Файл есть' : 'Файла нет' }}
                            <span v-if="episode.fileCodec"> · {{ episode.fileCodec.toUpperCase() }}</span>
                            <span v-if="episode.fileDateAdded"> · {{ formatDate(episode.fileDateAdded) }}</span>
                        </p>
                    </div>
                    <CheckCircle v-if="episode.hasFile" class="h-4 w-4 shrink-0 text-green-500" />
                    <Circle v-else class="h-4 w-4 shrink-0 text-muted-foreground" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import { CheckCircle, ChevronDown, Circle } from '@lucide/vue'

    const props = defineProps({
        episodes: { type: Array, default: () => [] },
    })

    const open = ref(false)
    const filesCount = computed(() => props.episodes.filter((episode) => episode.hasFile).length)

    function formatDate(date) {
        return new Date(date).toLocaleDateString('ru-RU')
    }
</script>
