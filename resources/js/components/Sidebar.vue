<template>
    <aside :class="classes">
        <div class="flex h-16 items-center justify-between border-b px-4">
            <div v-if="!collapsed" class="flex items-center gap-2">
                <Tv class="h-6 w-6 text-primary" />
                <span class="font-semibold">Aniarr</span>
            </div>
            <Tv v-else class="mx-auto h-6 w-6 text-primary" />
        </div>

        <div class="p-4">
            <Button @click="$emit('onAddClick')" :class="cn('w-full cursor-pointer gap-2', collapsed && 'px-2')">
                <PlusCircle class="h-4 w-4" />
                <span v-if="!collapsed">Добавить сериал</span>
            </Button>
        </div>

        <nav class="flex-1 space-y-1 px-2">
            <RouterLink
                v-for="item in navItems"
                :key="item.to"
                :to="item.to"
                :class="cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                    isActive(item)
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    collapsed && 'justify-center px-2',
                )"
            >
                <component :is="item.icon" class="h-4 w-4 shrink-0" />
                <span v-if="!collapsed">{{ item.label }}</span>
            </RouterLink>
        </nav>

        <div class="border-t p-4">
            <div :class="cn('flex items-center', collapsed ? 'justify-center gap-1' : 'justify-between')">
                <ThemeToggle />
                <Button variant="ghost" size="icon" @click="collapsed = !collapsed">
                    <component :is="collapsed ? ChevronRight : ChevronLeft" class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </aside>
</template>

<script setup>
    import {
        Activity,
        BookOpen,
        ChevronLeft,
        ChevronRight,
        Download,
        LayoutDashboard,
        PlusCircle,
        Settings,
        Tv,
    } from '@lucide/vue'
    import { computed, ref } from 'vue'
    import { useRoute } from 'vue-router'
    import Button from '@/components/ui/button/Button.vue'
    import ThemeToggle from '@/components/ThemeToggle.vue'
    import { cn } from '@/lib/utils'

    defineEmits(['onAddClick'])

    const route = useRoute()
    const collapsed = ref(false)

    const navItems = [
        { to: '/', icon: LayoutDashboard, label: 'Обзор', exact: true },
        { to: '/library', icon: BookOpen, label: 'Библиотека' },
        { to: '/downloads', icon: Download, label: 'Загрузки' },
        { to: '/activity', icon: Activity, label: 'Activity' },
        { to: '/settings', icon: Settings, label: 'Настройки' },
    ]

    const classes = computed(() => cn(
        'sticky top-0 hidden h-screen flex-col border-r bg-card transition-all duration-300 md:flex',
        collapsed.value ? 'w-16' : 'w-64',
    ))

    function isActive(item) {
        return item.exact ? route.path === item.to : route.path.startsWith(item.to)
    }
</script>
