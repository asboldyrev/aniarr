<template>
    <Card class="gap-0 p-0">
        <CardHeader class="flex flex-row items-start justify-between gap-3 p-4 pb-2 sm:p-5 sm:pb-2">
            <div class="min-w-0">
                <CardTitle class="text-sm font-medium text-muted-foreground">{{ title }}</CardTitle>
                <div class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ value }}</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                <component :is="icon" class="h-4 w-4 text-muted-foreground" />
            </div>
        </CardHeader>
        <CardContent class="p-4 pt-1 sm:p-5 sm:pt-1">
            <p class="text-xs leading-5 text-muted-foreground">{{ description }}</p>
        </CardContent>
    </Card>
</template>

<script setup>
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

    defineProps({
        title: { type: String, required: true },
        value: { type: [Number, String], required: true },
        icon: { type: [Object, Function], required: true },
        description: { type: String, required: true },
    })
</script>
