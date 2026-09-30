<template>
    <Card class="gap-0 p-0">
        <CardHeader class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <CardTitle class="text-lg">{{ title }}</CardTitle>
                    <CardDescription class="mt-1">{{ description }}</CardDescription>
                </div>

                <Badge v-if="status" variant="outline" :class="statusClass">
                    {{ statusLabel }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-4 p-4 pt-0 sm:p-5 sm:pt-0">
            <slot />

            <div
                v-if="status?.message"
                class="rounded-md border px-3 py-2 text-xs"
                :class="status.connected
                    ? 'border-emerald-500/30 bg-emerald-500/5 text-emerald-700 dark:text-emerald-300'
                    : 'border-red-500/30 bg-red-500/5 text-red-700 dark:text-red-300'"
            >
                {{ status.message }}
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-muted-foreground">
                    {{ dirty ? 'Сохраните изменения перед проверкой подключения.' : 'Проверка использует сохранённые значения.' }}
                </p>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="testing || dirty"
                    @click="$emit('test')"
                >
                    <Loader2 v-if="testing" class="mr-2 h-4 w-4 animate-spin" />
                    <PlugZap v-else class="mr-2 h-4 w-4" />
                    Проверить подключение
                </Button>
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
    import { computed } from 'vue'
    import { Loader2, PlugZap } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

    const props = defineProps({
        title: { type: String, required: true },
        description: { type: String, required: true },
        status: { type: Object, default: null },
        testing: { type: Boolean, default: false },
        dirty: { type: Boolean, default: false },
    })

    defineEmits(['test'])

    const statusLabel = computed(() => {
        if (! props.status) return ''
        return props.status.connected ? 'Подключено' : 'Нет подключения'
    })

    const statusClass = computed(() => props.status?.connected
        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
        : 'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300')
</script>
