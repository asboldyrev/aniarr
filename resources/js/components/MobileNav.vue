<template>
    <header class="sticky top-0 z-50 flex h-14 items-center justify-between border-b bg-card px-4 md:hidden">
        <div class="flex items-center gap-2">
            <Tv class="h-5 w-5 text-primary" />
            <span class="font-semibold">Series Manager</span>
        </div>

        <div class="flex items-center gap-2">
            <ThemeToggle />
            <Sheet :open="open" @change="setOpen">
                <SheetTrigger asChild>
                    <Button variant="ghost" size="icon">
                        <Menu class="h-5 w-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-72 p-0">
                    <SheetHeader class="border-b p-4">
                        <SheetTitle class="flex items-center gap-2">
                            <Tv class="h-5 w-5 text-primary" />
                            Series Manager
                        </SheetTitle>
                    </SheetHeader>

                    <div class="p-4">
                        <Button @click="handleAddClick" class="w-full gap-2">
                            <PlusCircle class="h-4 w-4" />
                            Добавить сериал
                        </Button>
                    </div>

                    <nav class="space-y-1 px-2">
                        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" @click="handleNavClick" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors" :class="isActive(item) ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                            <component v-if="item?.icon" :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="flex-1">{{ item.label }}</span>
                            <Badge v-if="item.badge !== undefined" :variant="item.badgeVariant || 'secondary'" class="h-5 min-w-5 justify-center px-1.5">
                                {{ item.badge }}
                            </Badge>
                        </RouterLink>
                    </nav>
                </SheetContent>
            </Sheet>
        </div>
    </header>
</template>

<script setup>
    import { Tv, Menu, PlusCircle, LayoutDashboard, Download, AlertCircle, Settings } from "@lucide/vue"
    import { Sheet, SheetTrigger, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";
    import ThemeToggle from "@/components/ThemeToggle.vue";
    import Button from "@/components/ui/button/Button.vue";
    import Badge from "@/components/ui/badge/Badge.vue";
    import { ref } from "vue";

    const open = ref(false)
    const activeDownloads = ref(0)
    const errorCount = ref(0)
    const navItems = ref([
        {
            to: '/',
            icon: LayoutDashboard,
            label: 'Dashboard',
            exact: true
        },
        {
            to: '/downloads',
            icon: Download,
            label: 'Активные загрузки',
            badge: activeDownloads > 0 ? activeDownloads : undefined
        },
        {
            to: '/errors',
            icon: AlertCircle,
            label: 'Ошибки',
            badge: errorCount > 0 ? errorCount : undefined,
            badgeVariant: 'destructive'
        },
        {
            to: '/settings',
            icon: Settings,
            label: 'Настройки'
        },
    ])

    function setOpen() {
        //
    }

    function handleAddClick() {
        //
    }

    function handleNavClick() {
        //
    }

    function isActive(item) {
        //
    }
</script>

<style lang="scss" scoped></style>
