<template>
    <div class="flex min-h-screen w-full flex-col bg-background md:flex-row" :class="{ dark: isDark }">
        <MobileNav @add-click="setAddDialogOpen(true)" />
        <Sidebar @on-add-click="setAddDialogOpen(true)" />

        <main class="min-w-0 flex-1 overflow-auto">
            <RouterView />
        </main>

        <AddSeriesDialog :open="addDialogOpen" @on-open-change="setAddDialogOpen" />
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue'
    import AddSeriesDialog from '@/components/AddSeriesDialog.vue'
    import MobileNav from '@/components/MobileNav.vue'
    import Sidebar from '@/components/Sidebar.vue'
    import useThemeStore from '@/stores/ThemeStore'

    const themeStore = useThemeStore()
    const addDialogOpen = ref(false)

    function setAddDialogOpen(state) {
        addDialogOpen.value = state
    }

    const isDark = computed(() => themeStore.isDark())
</script>
