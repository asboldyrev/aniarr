<template>
    <Card class="p-0 gap-0">
        <CardHeader class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
            <component :is="icon" class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent class="p-6 pt-0">
            <div class="text-2xl font-bold">{{ value }}</div>
            <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
        </CardContent>
    </Card>
</template>

<script setup>
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

    const props = defineProps({
        title: String,
        value: Number | String,
        icon: Object | Function,
        description: String,
        trend: {
            value: Number,
            isPositive: Boolean,
        }
    })
</script>
