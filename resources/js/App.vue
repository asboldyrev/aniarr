<template>
    <div class="flex min-h-screen w-full flex-col bg-background md:flex-row" :class="{ dark: isDark }">
        <MobileNav @Click="setAddDialogOpen(true)" />
        <Sidebar @onAddClick="() => setAddDialogOpen(true)" />
        <!-- <AppSidebar onAddClick={() => setAddDialogOpen(true)} /> -->
        <main className="flex-1 overflow-auto">
            <RouterView />
        </main>
        <AddSeriesDialog :open="addDialogOpen" @onOpenChange="() => setAddDialogOpen(true)" />
    </div>

</template>

<script setup>
    import Sidebar from '@/components/Sidebar.vue';
    import useThemeStore from '@/stores/ThemeStore';
    import AddSeriesDialog from '@/components/AddSeriesDialog.vue';
    import MobileNav from '@/components/MobileNav.vue';

    import { computed, ref } from 'vue';

    const themeStore = useThemeStore()

    const addDialogOpen = ref(false)

    function setAddDialogOpen(state) {
        addDialogOpen.value = state
        console.log('set open add modal')
    }

    const isDark = computed(() => {
        return themeStore.isDark()
    })

</script>
