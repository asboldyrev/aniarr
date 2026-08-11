<template>
    <Card class="gap-0 p-0">
        <CardHeader class="flex flex-row items-center justify-between gap-3 p-4 sm:p-5">
            <div>
                <CardTitle class="text-base">Требуют внимания</CardTitle>
                <CardDescription>Нерешённые предупреждения и ошибки</CardDescription>
            </div>
            <RouterLink to="/activity" class="text-sm text-muted-foreground hover:text-foreground">
                Все
            </RouterLink>
        </CardHeader>

        <CardContent class="p-0">
            <div v-if="items.length === 0" class="px-4 pb-5 text-sm text-muted-foreground sm:px-5">
                Всё в порядке — нерешённых событий нет.
            </div>

            <div v-else class="divide-y">
                <RouterLink
                    v-for="item in items.slice(0, 5)"
                    :key="item.id"
                    :to="item.seriesId ? `/series/${item.seriesId}` : '/activity'"
                    class="flex gap-3 px-4 py-4 transition-colors hover:bg-muted/50 sm:px-5"
                >
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted">
                        <CircleAlert v-if="item.type === 'error'" class="h-4 w-4" />
                        <TriangleAlert v-else class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="line-clamp-2 text-sm font-medium">{{ item.message }}</p>
                            <Badge :variant="item.type === 'error' ? 'destructive' : 'secondary'" class="shrink-0">
                                {{ item.type === 'error' ? 'Ошибка' : 'Warning' }}
                            </Badge>
                        </div>
                        <p class="mt-1 truncate text-xs text-muted-foreground">
                            {{ item.series?.title ?? item.source ?? 'Aniarr' }}
                        </p>
                    </div>
                </RouterLink>
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
    import { CircleAlert, TriangleAlert } from '@lucide/vue'
    import Badge from '@/components/ui/badge/Badge.vue'
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

    defineProps({
        items: { type: Array, default: () => [] },
    })
</script>
