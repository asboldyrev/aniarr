<template>
    <Dialog v-model:open="open">
        <Button variant="outline" size="sm" @click="open = true">
            <Settings2 class="mr-2 h-4 w-4" />
            {{ feed ? 'Изменить' : 'Настроить RSS' }}
        </Button>

        <DialogContent class="sm:max-w-lg">
            <template v-if="! deleteMode">
                <DialogHeader>
                    <DialogTitle>{{ feed ? 'Редактировать RSS' : 'Настроить RSS' }}</DialogTitle>
                    <DialogDescription>
                        URL и состояние RSS-ленты для сезона {{ seasonNumber }}.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="rss-manage-url">RSS URL</Label>
                        <Input id="rss-manage-url" v-model="rssUrl" type="url" placeholder="https://example.com/rss" />
                    </div>

                    <div class="flex items-start gap-3 rounded-lg border p-4">
                        <Checkbox id="rss-manage-enabled" v-model="enabled" class="mt-0.5" />
                        <div class="space-y-1">
                            <Label for="rss-manage-enabled" class="cursor-pointer">Лента включена</Label>
                            <p class="text-xs text-muted-foreground">
                                При активном monitoring включённая лента будет проверяться автоматически.
                            </p>
                        </div>
                    </div>

                    <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                        {{ error }}
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <Button v-if="feed" variant="ghost" class="text-destructive hover:text-destructive" :disabled="busy" @click="deleteMode = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Удалить RSS
                    </Button>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" :disabled="busy" @click="open = false">Отмена</Button>
                        <Button :disabled="busy || ! rssUrl.trim()" @click="save">
                            <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
                            Сохранить
                        </Button>
                    </div>
                </div>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>Удалить RSS-ленту?</DialogTitle>
                    <DialogDescription>
                        Настройка RSS и найденные по ней релизы будут удалены. Season и Episodes останутся.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="error" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                    {{ error }}
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button variant="outline" :disabled="busy" @click="deleteMode = false">Назад</Button>
                    <Button variant="destructive" :disabled="busy" @click="removeFeed">
                        <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
                        Удалить RSS
                    </Button>
                </div>
            </template>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { ref, watch } from 'vue'
    import { Loader2, Settings2, Trash2 } from '@lucide/vue'
    import { createRssFeed, deleteRssFeed, updateRssFeed } from '@/api/rssFeeds'
    import Button from '@/components/ui/button/Button.vue'
    import { Checkbox } from '@/components/ui/checkbox'
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
    import Input from '@/components/ui/input/Input.vue'
    import Label from '@/components/ui/label/Label.vue'

    const props = defineProps({
        feed: { type: Object, default: null },
        seasonId: { type: Number, required: true },
        seasonNumber: { type: Number, required: true },
    })

    const emit = defineEmits(['changed'])
    const open = ref(false)
    const deleteMode = ref(false)
    const rssUrl = ref('')
    const enabled = ref(true)
    const busy = ref(false)
    const error = ref(null)

    watch(open, (value) => {
        if (! value) return
        deleteMode.value = false
        error.value = null
        rssUrl.value = props.feed?.rssUrl ?? ''
        enabled.value = props.feed?.enabled ?? true
    })

    function errorMessage(exception) {
        return exception?.response?.data?.message
            ?? Object.values(exception?.response?.data?.errors ?? {}).flat()[0]
            ?? exception?.message
            ?? 'Не удалось сохранить RSS-ленту.'
    }

    async function save() {
        if (busy.value || ! rssUrl.value.trim()) return
        busy.value = true
        error.value = null

        try {
            const payload = { rss_url: rssUrl.value.trim(), enabled: enabled.value }
            if (props.feed) {
                await updateRssFeed(props.feed.id, payload)
            } else {
                await createRssFeed(props.seasonId, payload)
            }
            open.value = false
            emit('changed')
        } catch (exception) {
            error.value = errorMessage(exception)
        } finally {
            busy.value = false
        }
    }

    async function removeFeed() {
        if (! props.feed || busy.value) return
        busy.value = true
        error.value = null

        try {
            await deleteRssFeed(props.feed.id)
            open.value = false
            emit('changed')
        } catch (exception) {
            error.value = errorMessage(exception)
        } finally {
            busy.value = false
        }
    }
</script>
