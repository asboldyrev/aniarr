<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
        <div class="relative sm:col-span-2 xl:col-span-1">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                :model-value="searchQuery"
                class="pl-9"
                placeholder="Поиск по сериалу..."
                @update:model-value="$emit('update:searchQuery', $event)"
            />
        </div>

        <Select :model-value="statusFilter" @update:model-value="$emit('update:statusFilter', $event)">
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Все статусы" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Все статусы</SelectItem>
                <SelectItem value="pending">В очереди</SelectItem>
                <SelectItem value="preparing">Подготовка</SelectItem>
                <SelectItem value="downloading">Загрузка</SelectItem>
                <SelectItem value="importing">Импорт</SelectItem>
                <SelectItem value="completed">Завершено</SelectItem>
                <SelectItem value="cancelled">Отменено</SelectItem>
                <SelectItem value="failed">Ошибка</SelectItem>
            </SelectContent>
        </Select>

        <Select :model-value="triggerFilter" @update:model-value="$emit('update:triggerFilter', $event)">
            <SelectTrigger class="w-full">
                <SelectValue placeholder="Все запуски" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Все запуски</SelectItem>
                <SelectItem value="automatic">Автоматические</SelectItem>
                <SelectItem value="manual">Ручные</SelectItem>
            </SelectContent>
        </Select>

        <Button variant="outline" class="gap-2" @click="$emit('refresh')">
            <RefreshCw class="h-4 w-4" />
            Обновить
        </Button>
    </div>
</template>

<script setup>
    import { RefreshCw, Search } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Input } from '@/components/ui/input'
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

    defineProps({
        searchQuery: { type: String, default: '' },
        statusFilter: { type: String, default: 'all' },
        triggerFilter: { type: String, default: 'all' },
    })

    defineEmits(['update:searchQuery', 'update:statusFilter', 'update:triggerFilter', 'refresh'])
</script>
