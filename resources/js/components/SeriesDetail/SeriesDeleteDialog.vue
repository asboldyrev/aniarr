<template>
    <Dialog v-model:open="open">
        <Button variant="destructive" size="sm" @click="open = true">
            <Trash2 class="mr-2 h-4 w-4" />
            Удалить
        </Button>

        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Удалить сериал?</DialogTitle>
                <DialogDescription>
                    Сериал и связанные данные Aniarr будут удалены без возможности восстановления.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div v-if="hasActiveDownload" class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-300">
                    Сначала завершите или отмените активную загрузку этого сериала.
                </div>

                <div class="flex items-start gap-3 rounded-lg border border-destructive/30 p-4">
                    <Checkbox id="delete-from-sonarr" v-model="deleteFromSonarr" class="mt-0.5" />
                    <div class="space-y-1">
                        <Label for="delete-from-sonarr" class="cursor-pointer">Также удалить из Sonarr вместе со всеми файлами</Label>
                        <p class="text-xs leading-relaxed text-muted-foreground">
                            Sonarr удалит сам сериал и связанные с ним файлы с диска. Это действие необратимо.
                        </p>
                    </div>
                </div>

                <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                    {{ error }}
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" :disabled="busy" @click="open = false">Отмена</Button>
                <Button variant="destructive" :disabled="busy || hasActiveDownload" @click="removeSeries">
                    <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
                    Удалить сериал
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import { Loader2, Trash2 } from '@lucide/vue'
    import { useRouter } from 'vue-router'
    import Button from '@/components/ui/button/Button.vue'
    import { Checkbox } from '@/components/ui/checkbox'
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
    import Label from '@/components/ui/label/Label.vue'
    import { getActiveDownload } from '@/domain/series'
    import useSeriesStore from '@/stores/SeriesStore'

    const props = defineProps({
        series: { type: Object, required: true },
    })

    const router = useRouter()
    const seriesStore = useSeriesStore()
    const open = ref(false)
    const deleteFromSonarr = ref(false)
    const busy = ref(false)
    const error = ref(null)
    const hasActiveDownload = computed(() => Boolean(getActiveDownload(props.series)))

    async function removeSeries() {
        if (busy.value || hasActiveDownload.value) return

        busy.value = true
        error.value = null

        try {
            await seriesStore.destroy(props.series.id, deleteFromSonarr.value)
            open.value = false
            await router.push('/library')
        } catch (exception) {
            error.value = exception?.response?.data?.message
                ?? exception?.message
                ?? 'Не удалось удалить сериал.'
        } finally {
            busy.value = false
        }
    }
</script>
