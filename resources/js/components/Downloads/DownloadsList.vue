<template>
    <div class="space-y-3">
        <DownloadItemCard
            v-for="download in downloads"
            :key="download.id"
            :download="download"
        />
    </div>
</template>

<script setup>
    import DownloadItemCard from '@/components/Downloads/DownloadItemCard.vue'

    defineProps({
        downloads: { type: Array, default: () => [] },
    })
</script>
