<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button size="sm" :disabled="disabled">Скачать</Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Ручная загрузка релиза</DialogTitle>
                <DialogDescription>
                    {{ release.codec?.toUpperCase() }} · E{{ release.firstEpisode }}–E{{ release.lastEpisode }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="rounded-lg border p-3 text-sm">
                    <p class="font-medium line-clamp-2">{{ release.title }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Можно скачать весь релиз или только выбранные эпизоды.
                    </p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-sm font-medium">Эпизоды</p>
                        <Button variant="ghost" size="sm" @click="toggleAll">
                            {{ allSelected ? 'Снять все' : 'Выбрать все' }}
                        </Button>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                        <button
                            v-for="episode in coveredEpisodes"
                            :key="episode.id"
                            type="button"
                            class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-2 text-left transition-colors"
                            :class="selectedIds.includes(episode.id) ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'"
                            @click="toggleEpisode(episode.id)"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">E{{ episode.episodeNumber }} · {{ episode.title || 'Без названия' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ episode.hasFile ? `Есть файл${episode.fileCodec ? ` · ${episode.fileCodec.toUpperCase()}` : ''}` : 'Файла нет' }}
                                </p>
                            </div>
                            <Check v-if="selectedIds.includes(episode.id)" class="h-4 w-4 shrink-0" />
                        </button>
                    </div>
                </div>

                <p v-if="errorMessage" class="text-sm text-destructive">{{ errorMessage }}</p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" :disabled="submitting" @click="open = false">Отмена</Button>
                <Button :disabled="submitting || selectedIds.length === 0" @click="submit">
                    <RefreshCw v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                    Скачать {{ selectedIds.length }} эп.
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { computed, ref, watch } from 'vue'
    import { Check, RefreshCw } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog'
    import { downloadRelease } from '@/api/releases'

    const props = defineProps({
        release: { type: Object, required: true },
        episodes: { type: Array, default: () => [] },
        disabled: { type: Boolean, default: false },
    })

    const emit = defineEmits(['downloaded'])

    const open = ref(false)
    const submitting = ref(false)
    const errorMessage = ref('')
    const selectedIds = ref([])

    const coveredEpisodes = computed(() => props.episodes.filter((episode) =>
        episode.episodeNumber >= props.release.firstEpisode
        && episode.episodeNumber <= props.release.lastEpisode,
    ))

    const allSelected = computed(() => coveredEpisodes.value.length > 0
        && selectedIds.value.length === coveredEpisodes.value.length)

    watch(open, (value) => {
        if (! value) return
        selectedIds.value = coveredEpisodes.value.map((episode) => episode.id)
        errorMessage.value = ''
    })

    function toggleEpisode(id) {
        selectedIds.value = selectedIds.value.includes(id)
            ? selectedIds.value.filter((episodeId) => episodeId !== id)
            : [...selectedIds.value, id]
    }

    function toggleAll() {
        selectedIds.value = allSelected.value
            ? []
            : coveredEpisodes.value.map((episode) => episode.id)
    }

    async function submit() {
        submitting.value = true
        errorMessage.value = ''

        try {
            await downloadRelease(props.release.id, selectedIds.value)
            open.value = false
            emit('downloaded')
        } catch (error) {
            errorMessage.value = error.response?.data?.message ?? 'Не удалось запустить загрузку релиза.'
        } finally {
            submitting.value = false
        }
    }
</script>
