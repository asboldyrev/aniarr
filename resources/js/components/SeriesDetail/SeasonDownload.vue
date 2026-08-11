<template>
    <div v-if="download" class="rounded-lg border p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium">Активная загрузка</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ statusLabel(download.status) }}
                    <span v-if="download.release?.codec"> · {{ download.release.codec.toUpperCase() }}</span>
                </p>
            </div>
            <span class="text-sm tabular-nums text-muted-foreground">{{ progress }}%</span>
        </div>

        <Progress :model-value="progress" class="mt-3 h-2" />

        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
            <span v-if="download.etaSeconds !== null && download.etaSeconds !== undefined">ETA {{ formatEta(download.etaSeconds) }}</span>
            <RouterLink to="/downloads" class="hover:text-foreground">Открыть загрузки</RouterLink>
        </div>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import { Progress } from '@/components/ui/progress'

    const props = defineProps({
        download: { type: Object, default: null },
    })

    const progress = computed(() => Math.max(0, Math.min(100, Number(props.download?.progress ?? 0))))

    const labels = {
        pending: 'В очереди',
        preparing: 'Подготовка',
        downloading: 'Загрузка',
        importing: 'Импорт в Sonarr',
    }

    function statusLabel(status) {
        return labels[status] ?? status
    }

    function formatEta(seconds) {
        const value = Math.max(0, Number(seconds ?? 0))
        const hours = Math.floor(value / 3600)
        const minutes = Math.floor((value % 3600) / 60)
        const secs = value % 60

        return [hours, minutes, secs].map((part) => String(part).padStart(2, '0')).join(':')
    }
</script>
