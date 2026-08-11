<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-sm">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                :model-value="searchQuery"
                class="pl-9"
                placeholder="Поиск по названию..."
                @update:model-value="$emit('update:searchQuery', $event)"
            />
        </div>

        <Select :model-value="statusFilter" @update:model-value="$emit('update:statusFilter', $event)">
            <SelectTrigger class="w-full sm:w-52">
                <SelectValue placeholder="Все статусы" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Все статусы</SelectItem>
                <SelectItem value="monitoring">Мониторинг</SelectItem>
                <SelectItem value="unmonitored">Отключён</SelectItem>
                <SelectItem value="pending">В очереди</SelectItem>
                <SelectItem value="preparing">Подготовка</SelectItem>
                <SelectItem value="downloading">Загрузка</SelectItem>
                <SelectItem value="importing">Импорт</SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>

<script setup>
    import { Search } from '@lucide/vue'
    import { Input } from '@/components/ui/input'
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

    defineProps({
        searchQuery: { type: String, default: '' },
        statusFilter: { type: String, default: 'all' },
    })

    defineEmits(['update:searchQuery', 'update:statusFilter'])
</script>
