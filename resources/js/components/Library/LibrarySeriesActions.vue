<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-8 w-8 shrink-0">
                <MoreHorizontal class="h-4 w-4" />
                <span class="sr-only">Действия</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem as-child>
                <RouterLink :to="`/series/${series.id}`" class="flex items-center gap-2">
                    <Eye class="h-4 w-4" />
                    Подробнее
                </RouterLink>
            </DropdownMenuItem>

            <DropdownMenuItem as-child>
                <a
                    :href="`https://thetvdb.com/?id=${series.thetvdbId}&tab=series`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-2"
                >
                    <ExternalLink class="h-4 w-4" />
                    TheTVDB
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup>
    import { ExternalLink, Eye, MoreHorizontal } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu'

    defineProps({
        series: {
            type: Object,
            required: true,
        },
    })
</script>
