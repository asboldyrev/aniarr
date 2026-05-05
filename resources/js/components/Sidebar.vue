<template>
    <aside :class="classes">
        <!-- Header -->
        <div class="flex h-16 items-center justify-between border-b px-4">
            <div v-if="!collapsed" class="flex items-center gap-2">
                <Tv class="h-6 w-6 text-primary" />
                <span class="font-semibold">Series Manager</span>
            </div>
            <Tv v-if="collapsed" class="h-6 w-6 text-primary mx-auto" />
        </div>

        <!-- Add Button -->
        <div class="p-4">
            <Button @click="props.onAddClick" :class="cn('cursor-pointer w-full gap-2', collapsed && 'px-2')">
                <PlusCircle class="h-4 w-4" />
                <span v-if="!collapsed">Добавить сериал</span>
            </Button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 px-2">
            <RouterLink v-for="item in navItems" :key="item.to" :class="cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors cursor-pointer',
                isActive(item)
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                collapsed && 'justify-center px-2'
            )" :to="item.to">
                <component :is="item.icon" class="h-4 w-4 shrink-0" />

                <div v-if="!collapsed" class="flex flex-1 items-center justify-between">
                    <span>{{ item.label }}</span>
                    <Badge v-if="item.badge !== undefined" :variant="item.badgeVariant || 'secondary'" class="h-5 min-w-5 justify-center px-1.5">
                        {{ item.badge }}
                    </Badge>
                </div>

                <Badge v-if="collapsed && item.badge !== undefined" :variant="item.badgeVariant || 'secondary'" class="absolute -right-1 -top-1 h-4 min-w-4 justify-center px-1 text-xs">
                    {{ item.badge }}
                </Badge>
            </RouterLink>
        </nav>

        <!-- Footer -->
        <div class="border-t p-4">
            <div :class="cn('flex items-center', collapsed ? 'justify-center' : 'justify-between')">
                <ThemeToggle v-if="!collapsed" />
                <Button variant="ghost" size="icon" @click="collapsed = !collapsed">
                    <component :is="collapsed ? ChevronRight : ChevronLeft" class="h-4 w-4" />
                </Button>
                <ThemeToggle v-if="collapsed" />
            </div>
        </div>
    </aside>
</template>

<script setup>
    import { Tv, PlusCircle, LayoutDashboard, ChevronRight, ChevronLeft, Download, AlertCircle, Settings } from '@lucide/vue';
    import Button from '@/components/ui/button/Button.vue';
    import ThemeToggle from '@/components/ThemeToggle.vue'
    import Badge from './ui/badge/Badge.vue';

    import { cn } from '@/lib/utils';
    import { computed, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';

    const props = defineProps()

    const route = useRoute()
    const router = useRouter()
    const collapsed = ref(false)

    // Навигационные элементы
    const navItems = computed(() => [
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
            // badge: activeDownloads.value > 0 ? activeDownloads.value : undefined
            badge: 3
        },
        {
            to: '/errors',
            icon: AlertCircle,
            label: 'Ошибки',
            // badge: errorCount.value > 0 ? errorCount.value : undefined,
            badge: 1,
            badgeVariant: 'destructive'
        },
        {
            to: '/settings',
            icon: Settings,
            label: 'Настройки'
        },
    ])

    const classes = computed(() => {
        const width = collapsed.value ? 'w-16' : 'w-64'
        return cn('sticky top-0 h-screen flex-col border-r bg-card transition-all duration-300 md:flex ' + width)
    })

    // Функция для проверки активного маршрута
    const isActive = (item) => {
        if (item.exact) {
            return route.path === item.to
        }

        return route.path.startsWith(item.to)
    }

    const navigate = (to) => {
        router.push(to)
    }
</script>

<style lang="scss" scoped></style>
