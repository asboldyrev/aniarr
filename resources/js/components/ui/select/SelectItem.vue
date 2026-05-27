<script setup lang="ts">
    import type { SelectItemProps } from 'reka-ui'

    import type { HTMLAttributes } from 'vue'
    import { reactiveOmit } from '@vueuse/core'
    import { Check } from '@lucide/vue'
    import {
        SelectItem,
        SelectItemIndicator,
        SelectItemText,
        useForwardProps,
    } from 'reka-ui'
    import { cn } from '@/lib/utils'

    const props = defineProps<SelectItemProps & { class?: HTMLAttributes['class'] }>()

    const delegatedProps = reactiveOmit(props, 'class')

    const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
    <SelectItem data-slot="select-item" v-bind="forwardedProps" :class="cn(
        'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pr-8 pl-1.5 text-sm outline-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 focus:bg-accent focus:text-accent-foreground not-data-[variant=destructive]:focus:**:text-accent-foreground gap-1.5 [&_svg:not([class*=size-])]:size-4 *:[span]:last:flex *:[span]:last:items-center *:[span]:last:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0',
        props.class,
    )
        ">
        <span class="pointer-events-none absolute right-2 flex size-4 items-center justify-center">
            <SelectItemIndicator>
                <slot name="indicator-icon">
                    <Check class="pointer-events-none" />
                </slot>
            </SelectItemIndicator>
        </span>

        <SelectItemText>
            <slot />
        </SelectItemText>
    </SelectItem>
</template>
