<template>
    <template v-if="canCancel">
        <Button variant="outline" size="sm" :disabled="busy" @click="confirmCancelOpen = true">
            <XCircle class="mr-2 h-4 w-4" />
            Отменить
        </Button>

        <Dialog v-model:open="confirmCancelOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Отменить Download?</DialogTitle>
                    <DialogDescription>
                        Torrent и его временные файлы будут удалены из qBittorrent. Уже импортированные в Sonarr файлы не затрагиваются.
                    </DialogDescription>
                </DialogHeader>

                <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

                <DialogFooter>
                    <Button variant="outline" :disabled="busy" @click="confirmCancelOpen = false">Назад</Button>
                    <Button variant="destructive" :disabled="busy" @click="handleCancel">
                        <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
                        Отменить загрузку
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </template>

    <template v-else-if="canRetry">
        <Button variant="outline" size="sm" :disabled="busy" @click="confirmRetryOpen = true">
            <RotateCcw class="mr-2 h-4 w-4" />
            Повторить
        </Button>

        <Dialog v-model:open="confirmRetryOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Повторить Download?</DialogTitle>
                    <DialogDescription>
                        Будет создан новый Download для того же релиза и тех же эпизодов. Текущая запись останется в истории.
                    </DialogDescription>
                </DialogHeader>

                <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

                <DialogFooter>
                    <Button variant="outline" :disabled="busy" @click="confirmRetryOpen = false">Назад</Button>
                    <Button :disabled="busy" @click="handleRetry">
                        <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
                        Создать новую попытку
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </template>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import { Loader2, RotateCcw, XCircle } from '@lucide/vue'
    import { cancelDownload, retryDownload } from '@/api/downloads'
    import Button from '@/components/ui/button/Button.vue'
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog'

    const props = defineProps({
        download: { type: Object, required: true },
    })

    const emit = defineEmits(['changed'])

    const cancellableStatuses = new Set(['pending', 'preparing', 'downloading'])
    const retryableStatuses = new Set(['failed', 'cancelled'])

    const confirmCancelOpen = ref(false)
    const confirmRetryOpen = ref(false)
    const busy = ref(false)
    const error = ref(null)

    const canCancel = computed(() => cancellableStatuses.has(props.download.status))
    const canRetry = computed(() => retryableStatuses.has(props.download.status))

    function errorMessage(exception) {
        return exception?.response?.data?.message
            ?? exception?.message
            ?? 'Не удалось выполнить действие с Download.'
    }

    async function handleCancel() {
        if (busy.value) return

        busy.value = true
        error.value = null

        try {
            await cancelDownload(props.download.id)
            confirmCancelOpen.value = false
            emit('changed')
        } catch (exception) {
            error.value = errorMessage(exception)
        } finally {
            busy.value = false
        }
    }

    async function handleRetry() {
        if (busy.value) return

        busy.value = true
        error.value = null

        try {
            await retryDownload(props.download.id)
            confirmRetryOpen.value = false
            emit('changed')
        } catch (exception) {
            error.value = errorMessage(exception)
        } finally {
            busy.value = false
        }
    }
</script>
