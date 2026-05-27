<script setup lang="ts">
    import type { SelectTriggerProps } from 'reka-ui'

    import type { HTMLAttributes } from 'vue'
    import { reactiveOmit } from '@vueuse/core'
    import { ChevronDown } from 'lucide-vue-next'
    import { SelectIcon, SelectTrigger, useForwardProps } from 'reka-ui'
    import { cn } from '@/lib/utils'

    const props = withDefaults(
        defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'], size?: 'sm' | 'default' }>(),
        { size: 'default' },
    )

    const delegatedProps = reactiveOmit(props, 'class', 'size')
    const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
    <SelectTrigger data-slot="select-trigger" :data-size="size" v-bind="forwardedProps" :class="cn(
        'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1 data-placeholder:text-muted-foreground dark:bg-input/30 dark:hover:bg-input/50 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:aria-invalid:border-destructive/50 gap-1.5 transition-colors select-none focus-visible:ring-3 aria-invalid:ring-3 data-[size=default]:h-10 data-[size=sm]:h-8 data-[size=sm]:rounded-[min(var(--radius-md),10px)] *:data-[slot=select-value]:gap-1.5 [&_svg:not([class*=size-])]:size-4 whitespace-nowrap *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center [&_svg]:pointer-events-none [&_svg]:shrink-0',
        props.class,
    )">
        <slot />
        <SelectIcon as-child>
            <ChevronDown class="text-muted-foreground size-4 pointer-events-none" />
        </SelectIcon>
    </SelectTrigger>
</template>
