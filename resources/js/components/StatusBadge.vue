<template>
    <Badge variant="secondary" class="text-white gap-1.5" :class="activeStatus.color">
        <component v-if="showIcon" :is="activeStatus.icon" :stroke-width="3" class="h-3 w-3" />
        {{ activeStatus.label }}
    </Badge>
</template>

<script setup>
    import { computed } from 'vue'
    import { Clock, RefreshCw, Download, CheckCircle, AlertCircle, PauseCircle } from '@lucide/vue'
    import Badge from './ui/badge/Badge.vue'

    const props = defineProps({
        status: {
            type: String,
            required: true,
        },
        showIcon: Boolean,
    })

    const STATUS_CONFIG = {
        monitoring: {
            label: 'Мониторинг',
            color: 'bg-blue-500',
            icon: Clock,
        },
        unmonitored: {
            label: 'Отключён',
            color: 'bg-slate-500',
            icon: PauseCircle,
        },
        pending: {
            label: 'В очереди',
            color: 'bg-amber-500',
            icon: Clock,
        },
        preparing: {
            label: 'Подготовка',
            color: 'bg-amber-500',
            icon: RefreshCw,
        },
        downloading: {
            label: 'Загрузка',
            color: 'bg-orange-500',
            icon: Download,
        },
        importing: {
            label: 'Импорт',
            color: 'bg-purple-500',
            icon: RefreshCw,
        },
        completed: {
            label: 'Завершено',
            color: 'bg-green-500',
            icon: CheckCircle,
        },
        cancelled: {
            label: 'Отменено',
            color: 'bg-slate-500',
            icon: PauseCircle,
        },
        failed: {
            label: 'Ошибка',
            color: 'bg-red-500',
            icon: AlertCircle,
        },
    }

    const activeStatus = computed(() => STATUS_CONFIG[props.status] ?? STATUS_CONFIG.monitoring)
</script>
