<template>
    <div class="rounded-lg border bg-card p-3 sm:p-4">
        <div class="grid gap-3 lg:grid-cols-[minmax(240px,1fr)_220px_200px_auto] lg:items-center">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :model-value="searchQuery"
                    class="pl-9"
                    placeholder="Поиск по названию..."
                    @update:model-value="$emit('update:searchQuery', $event)"
                />
            </div>

            <Select :model-value="statusFilter" @update:model-value="$emit('update:statusFilter', $event)">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Все состояния" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Все состояния</SelectItem>
                    <SelectItem value="monitoring">Мониторинг</SelectItem>
                    <SelectItem value="unmonitored">Отключённые</SelectItem>
                    <SelectItem value="active">Активная загрузка</SelectItem>
                    <SelectItem value="incomplete">Неполные</SelectItem>
                </SelectContent>
            </Select>

            <Select :model-value="codecFilter" @update:model-value="$emit('update:codecFilter', $event)">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Все форматы" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Все форматы</SelectItem>
                    <SelectItem value="hevc">HEVC</SelectItem>
                    <SelectItem value="avc">AVC</SelectItem>
                    <SelectItem value="mixed">AVC + HEVC</SelectItem>
                    <SelectItem value="none">Без файлов</SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="hasFilters"
                variant="ghost"
                class="justify-center lg:justify-start"
                @click="$emit('reset')"
            >
                <RotateCcw class="h-4 w-4" />
                Сбросить
            </Button>
        </div>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import { RotateCcw, Search } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Input } from '@/components/ui/input'
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

    const props = defineProps({
        searchQuery: { type: String, default: '' },
        statusFilter: { type: String, default: 'all' },
        codecFilter: { type: String, default: 'all' },
    })

    defineEmits([
        'update:searchQuery',
        'update:statusFilter',
        'update:codecFilter',
        'reset',
    ])

    const hasFilters = computed(() =>
        props.searchQuery.trim() !== ''
        || props.statusFilter !== 'all'
        || props.codecFilter !== 'all',
    )
</script>
