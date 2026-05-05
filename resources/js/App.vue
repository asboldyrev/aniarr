<template>
    <div class="flex min-h-screen w-full flex-col bg-background md:flex-row" :class="{ dark: isDark }">
        <!-- <MobileNav onAddClick={() => setAddDialogOpen(true)} /> -->
        <Sidebar />
        <!-- <AppSidebar onAddClick={() => setAddDialogOpen(true)} /> -->
        <main className="flex-1 overflow-auto">
            <!-- <Outlet /> -->
            <RouterView />
        </main>
        <!-- <AddSeriesDialog open={addDialogOpen} onOpenChange={setAddDialogOpen} /> -->
    </div>

</template>

<script setup>
    import Sidebar from '@/components/Sidebar.vue';
    import useThemeStore from '@/stores/ThemeStore';
    import { computed } from 'vue';

    const themeStore = useThemeStore()

    const isDark = computed(() => {
        return themeStore.isDark()
    })

</script>
