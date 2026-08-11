<template>
    <div class="space-y-3">
        <ActivityItem
            v-for="item in items"
            :key="item.id"
            :item="item"
            :busy="busyIds.has(item.id)"
            @resolve="$emit('resolve', $event)"
            @reopen="$emit('reopen', $event)"
        />
    </div>
</template>

<script setup>
    import ActivityItem from '@/components/Activity/ActivityItem.vue'

    defineProps({
        items: { type: Array, default: () => [] },
        busyIds: { type: Set, default: () => new Set() },
    })

    defineEmits(['resolve', 'reopen'])
</script>
