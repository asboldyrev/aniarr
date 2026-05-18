<template>
    <Badge variant="secondary" class="text-white gap-1.5" :class="activeStatus.color">
        <component v-if="showIcon" :is="activeStatus.icon" :stroke-width="3" class="h-3 w-3" />
        {{ activeStatus.label }}
    </Badge>
</template>

<script setup>
    import { Clock, Bell, RefreshCw, Download, CheckCircle, AlertCircle } from '@lucide/vue';

    import Badge from './ui/badge/Badge.vue';
    import { ref } from 'vue';

    const props = defineProps({
        status: String,
        showIcon: Boolean
    })

    const STATUS_CONFIG = {
        waiting: {
            label: 'Ожидание',
            color: 'bg-blue-500',
            icon: Clock
        },
        new_episodes: {
            label: 'Новые серии',
            color: 'bg-yellow-500',
            icon: Bell
        },
        downloading_avc: {
            label: 'Загрузка AVC',
            color: 'bg-orange-500',
            icon: Download
        },
        processing_sonarr: {
            label: 'Обработка Sonarr',
            color: 'bg-amber-500',
            icon: RefreshCw
        },
        downloading_hevc: {
            label: 'Загрузка HEVC',
            color: 'bg-orange-600',
            icon: Download
        },
        syncing_jellyfin: {
            label: 'Синхронизация Jellyfin',
            color: 'bg-purple-500',
            icon: RefreshCw
        },
        done: {
            label: 'Готово',
            color: 'bg-green-500',
            icon: CheckCircle
        },
        error: {
            label: 'Ошибка',
            color: 'bg-red-500',
            icon: AlertCircle
        },
    }

    const activeStatus = ref(STATUS_CONFIG[props.status])
</script>

<style lang="scss" scoped></style>
