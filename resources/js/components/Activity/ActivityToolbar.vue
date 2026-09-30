<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Select :model-value="typeFilter" @update:model-value="$emit('update:typeFilter', $event)">
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Все типы" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Все типы</SelectItem>
                <SelectItem value="error">Ошибки</SelectItem>
                <SelectItem value="warning">Предупреждения</SelectItem>
                <SelectItem value="info">Информация</SelectItem>
                <SelectItem value="debug">Отладка</SelectItem>
            </SelectContent>
        </Select>

        <Select :model-value="stateFilter" @update:model-value="$emit('update:stateFilter', $event)">
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Состояние" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="unresolved">Требуют внимания</SelectItem>
                <SelectItem value="all">Все записи</SelectItem>
            </SelectContent>
        </Select>

        <Select :model-value="sourceFilter" @update:model-value="$emit('update:sourceFilter', $event)">
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Все источники" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Все источники</SelectItem>
                <SelectItem value="sonarr">Sonarr</SelectItem>
                <SelectItem value="qbittorrent">qBittorrent</SelectItem>
                <SelectItem value="rss">RSS</SelectItem>
            </SelectContent>
        </Select>

        <Button variant="outline" class="w-full" @click="$emit('refresh')">
            <RefreshCw class="mr-2 h-4 w-4" />
            Обновить
        </Button>
    </div>
</template>

<script setup>
    import { RefreshCw } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

    defineProps({
        typeFilter: { type: String, default: 'all' },
        stateFilter: { type: String, default: 'unresolved' },
        sourceFilter: { type: String, default: 'all' },
    })

    defineEmits([
        'update:typeFilter',
        'update:stateFilter',
        'update:sourceFilter',
        'refresh',
    ])
</script>
