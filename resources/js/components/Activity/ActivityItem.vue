<template>
    <div class="rounded-lg border bg-card">
        <div class="flex flex-col gap-4 p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md" :class="iconClass">
                    <component :is="typeIcon" class="h-4 w-4" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-medium sm:text-base">{{ item.message }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                <span>{{ sourceLabel }}</span>
                                <span v-if="item.series">·</span>
                                <RouterLink
                                    v-if="item.series"
                                    :to="`/series/${item.series.id}`"
                                    class="hover:text-foreground hover:underline"
                                >
                                    {{ item.series.title }}
                                </RouterLink>
                                <span v-if="item.season">· S{{ String(item.season.number).padStart(2, '0') }}</span>
                                <span>· {{ formattedDate }}</span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline" :class="badgeClass">{{ typeLabel }}</Badge>
                            <Badge v-if="item.resolvedAt" variant="outline" class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">Решено</Badge>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <Button v-if="hasDetails" variant="outline" size="sm" @click="detailsOpen = true">
                            <FileJson class="mr-2 h-4 w-4" />
                            Детали
                        </Button>

                        <Button
                            v-if="canResolve"
                            variant="outline"
                            size="sm"
                            :disabled="busy"
                            @click="$emit('resolve', item)"
                        >
                            <Check class="mr-2 h-4 w-4" />
                            Решено
                        </Button>

                        <Button
                            v-else-if="canReopen"
                            variant="outline"
                            size="sm"
                            :disabled="busy"
                            @click="$emit('reopen', item)"
                        >
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Вернуть
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="detailsOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Детали события</DialogTitle>
                    <DialogDescription>
                        {{ item.event || item.source || 'Activity' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[60vh] overflow-auto rounded-md bg-muted p-3">
                    <pre class="whitespace-pre-wrap break-words text-xs leading-relaxed">{{ formattedContext }}</pre>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import { AlertCircle, AlertTriangle, Bug, Check, FileJson, Info, RotateCcw } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog'

    const props = defineProps({
        item: { type: Object, required: true },
        busy: { type: Boolean, default: false },
    })

    defineEmits(['resolve', 'reopen'])

    const detailsOpen = ref(false)

    const config = computed(() => ({
        error: {
            label: 'Ошибка',
            icon: AlertCircle,
            iconClass: 'bg-red-500/10 text-red-600 dark:text-red-400',
            badgeClass: 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300',
        },
        warning: {
            label: 'Предупреждение',
            icon: AlertTriangle,
            iconClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            badgeClass: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        },
        info: {
            label: 'Информация',
            icon: Info,
            iconClass: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
            badgeClass: 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300',
        },
        debug: {
            label: 'Отладка',
            icon: Bug,
            iconClass: 'bg-muted text-muted-foreground',
            badgeClass: 'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
        },
    }[props.item.type] ?? {
        label: props.item.type ?? 'Событие',
        icon: Info,
        iconClass: 'bg-muted text-muted-foreground',
        badgeClass: 'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
    }))

    const typeLabel = computed(() => config.value.label)
    const typeIcon = computed(() => config.value.icon)
    const iconClass = computed(() => config.value.iconClass)
    const badgeClass = computed(() => config.value.badgeClass)

    const sourceLabel = computed(() => {
        const source = String(props.item.source ?? '').toLowerCase()

        return {
            sonarr: 'Sonarr',
            qbittorrent: 'qBittorrent',
            rss: 'RSS',
            jellyfin: 'Jellyfin',
        }[source] ?? props.item.source ?? 'Aniarr'
    })

    const formattedDate = computed(() => {
        if (! props.item.createdAt) return '—'
        return new Date(props.item.createdAt).toLocaleString('ru-RU')
    })

    const hasDetails = computed(() => {
        const context = props.item.context
        return context !== null && context !== undefined && Object.keys(context).length > 0
    })

    const formattedContext = computed(() => JSON.stringify(props.item.context ?? {}, null, 2))
    const canResolve = computed(() => ['error', 'warning'].includes(props.item.type) && ! props.item.resolvedAt)
    const canReopen = computed(() => ['error', 'warning'].includes(props.item.type) && Boolean(props.item.resolvedAt))
</script>
