<template>
    <div class="rounded-lg border p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium">RSS</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ statusLabel }}</p>
            </div>
            <Badge :variant="statusVariant">{{ shortLabel }}</Badge>
        </div>

        <div v-if="feed" class="mt-3 space-y-1 text-xs text-muted-foreground">
            <p v-if="feed.lastRssCheck">Проверен: {{ formatDate(feed.lastRssCheck) }}</p>
            <p v-if="feed.lastRssSuccessAt">Успешно: {{ formatDate(feed.lastRssSuccessAt) }}</p>
            <p v-if="feed.lastError" class="text-destructive">{{ feed.lastError }}</p>
            <a
                v-if="feed.rssUrl"
                :href="feed.rssUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 pt-1 hover:text-foreground"
            >
                Открыть RSS
                <ExternalLink class="h-3 w-3" />
            </a>
        </div>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import { ExternalLink } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'

    const props = defineProps({
        feed: { type: Object, default: null },
    })

    const state = computed(() => {
        const feed = props.feed
        if (! feed) return 'missing'

        const hasCurrentError = feed.lastErrorAt
            && feed.lastError
            && (! feed.lastRssSuccessAt || new Date(feed.lastErrorAt) >= new Date(feed.lastRssSuccessAt))

        if (hasCurrentError) return 'error'
        if (feed.enabled) return 'healthy'
        return 'disabled'
    })

    const shortLabel = computed(() => ({
        healthy: 'Активен',
        error: 'Ошибка',
        disabled: 'Отключён',
        missing: 'Нет RSS',
    }[state.value]))

    const statusLabel = computed(() => ({
        healthy: 'Лента включена и работает',
        error: 'Последняя проверка завершилась ошибкой',
        disabled: 'Лента настроена, но отключена',
        missing: 'RSS-лента для сезона не настроена',
    }[state.value]))

    const statusVariant = computed(() => state.value === 'error' ? 'destructive' : 'outline')

    function formatDate(date) {
        if (! date) return '—'
        return new Date(date).toLocaleString('ru-RU')
    }
</script>
