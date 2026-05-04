<template>
    <div className="flex min-h-screen w-full flex-col bg-background md:flex-row">
        <!-- <MobileNav onAddClick={() => setAddDialogOpen(true)} /> -->
        <!-- <AppSidebar onAddClick={() => setAddDialogOpen(true)} /> -->
        <main className="flex-1 overflow-auto">
            <!-- <Outlet /> -->
            <EmptyState :icon="RiTvLine" title="Нет добавленных сериалов" description="Добавьте первый сериал, чтобы начать отслеживание обновлений и автоматическую загрузку" />
        </main>
        <!-- <AddSeriesDialog open={addDialogOpen} onOpenChange={setAddDialogOpen} /> -->
    </div>

</template>

<script setup>
    import EmptyState from './components/EmptyState.vue';
    import { RiTvLine } from '@remixicon/vue';

</script>
