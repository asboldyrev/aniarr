<template>
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <DashboardStatCard
            title="Сериалы"
            :value="totalSeries"
            :icon="Tv"
            description="Всего в библиотеке"
        />
        <DashboardStatCard
            title="Активные загрузки"
            :value="activeDownloads"
            :icon="Download"
            :description="activeDownloads > 0 ? 'Есть выполняющиеся Download' : 'Сейчас ничего не загружается'"
        />
        <DashboardStatCard
            title="Ожидают обновлений"
            :value="waitingForUpdates"
            :icon="Clock3"
            description="Мониторятся и ждут новых Release"
        />
        <DashboardStatCard
            title="Требуют внимания"
            :value="attentionCount"
            :icon="TriangleAlert"
            :description="attentionCount > 0 ? 'Есть нерешённые предупреждения или ошибки' : 'Нерешённых проблем нет'"
        />
    </section>
</template>

<script setup>
    import { Clock3, Download, TriangleAlert, Tv } from '@lucide/vue'
    import DashboardStatCard from '@/components/Dashboard/DashboardStatCard.vue'

    defineProps({
        totalSeries: { type: Number, default: 0 },
        activeDownloads: { type: Number, default: 0 },
        waitingForUpdates: { type: Number, default: 0 },
        attentionCount: { type: Number, default: 0 },
    })
</script>
