<!-- EmptyState.vue -->
<script setup lang="ts">
    import { type Component } from 'vue'

    interface EmptyStateProps {
        icon: Component
        title: string
        description: string
        iconClassName?: string
    }

    const props = withDefaults(defineProps<EmptyStateProps>(), {
        iconClassName: 'text-muted-foreground'
    })
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
        <div :class="`mb-4 rounded-full bg-muted p-4 ${iconClassName}`">
            <component :is="icon" class="h-8 w-8" />
        </div>
        <h3 class="mb-2 text-lg font-semibold">{{ title }}</h3>
        <p class="mb-6 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <slot name="action" />
    </div>
</template>
