<template>
    <header class="sticky top-0 z-50 flex h-14 items-center justify-between border-b bg-card px-4 md:hidden">
        <RouterLink to="/" class="flex min-w-0 items-center gap-2">
            <Tv class="h-5 w-5 shrink-0 text-primary" />
            <span class="truncate font-semibold">Aniarr</span>
        </RouterLink>

        <div class="flex items-center gap-1">
            <ThemeToggle />

            <Sheet v-model:open="open">
                <SheetTrigger asChild>
                    <Button variant="ghost" size="icon" aria-label="Открыть меню">
                        <Menu class="h-5 w-5" />
                    </Button>
                </SheetTrigger>

                <SheetContent side="left" class="w-[min(18rem,calc(100vw-2rem))] p-0">
                    <SheetHeader class="border-b p-4">
                        <SheetTitle class="flex items-center gap-2">
                            <Tv class="h-5 w-5 text-primary" />
                            Aniarr
                        </SheetTitle>
                    </SheetHeader>

                    <div class="p-4">
                        <Button class="w-full gap-2" @click="handleAddClick">
                            <PlusCircle class="h-4 w-4" />
                            Добавить сериал
                        </Button>
                    </div>

                    <nav class="space-y-1 px-2 pb-4">
                        <RouterLink
                            v-for="item in navItems"
                            :key="item.to"
                            :to="item.to"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors"
                            :class="isActive(item)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="open = false"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span>{{ item.label }}</span>
                        </RouterLink>
                    </nav>
                </SheetContent>
            </Sheet>
        </div>
    </header>
</template>

<script setup>
    import {
        Activity,
        BookOpen,
        Download,
        LayoutDashboard,
        Menu,
        PlusCircle,
        Settings,
        Tv,
    } from '@lucide/vue'
    import { ref } from 'vue'
    import { useRoute } from 'vue-router'
    import Button from '@/components/ui/button/Button.vue'
    import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet'
    import ThemeToggle from '@/components/ThemeToggle.vue'

    const emit = defineEmits(['addClick'])
    const route = useRoute()
    const open = ref(false)

    const navItems = [
        { to: '/', icon: LayoutDashboard, label: 'Обзор', exact: true },
        { to: '/library', icon: BookOpen, label: 'Библиотека' },
        { to: '/downloads', icon: Download, label: 'Загрузки' },
        { to: '/activity', icon: Activity, label: 'Activity' },
        { to: '/settings', icon: Settings, label: 'Настройки' },
    ]

    function handleAddClick() {
        open.value = false
        emit('addClick')
    }

    function isActive(item) {
        return item.exact ? route.path === item.to : route.path.startsWith(item.to)
    }
</script>
