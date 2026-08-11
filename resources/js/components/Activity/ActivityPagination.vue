<template>
    <div v-if="lastPage > 1" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted-foreground">
            Страница {{ currentPage }} из {{ lastPage }}
        </p>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage <= 1 || loading"
                @click="$emit('change', currentPage - 1)"
            >
                <ChevronLeft class="mr-1 h-4 w-4" />
                Назад
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage >= lastPage || loading"
                @click="$emit('change', currentPage + 1)"
            >
                Далее
                <ChevronRight class="ml-1 h-4 w-4" />
            </Button>
        </div>
    </div>
</template>

<script setup>
    import { ChevronLeft, ChevronRight } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'

    defineProps({
        currentPage: { type: Number, default: 1 },
        lastPage: { type: Number, default: 1 },
        loading: { type: Boolean, default: false },
    })

    defineEmits(['change'])
</script>
