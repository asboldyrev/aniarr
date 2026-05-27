<script setup lang="ts">
    import type { DropdownMenuRadioItemEmits, DropdownMenuRadioItemProps } from 'reka-ui'

    import type { HTMLAttributes } from 'vue'
    import { reactiveOmit } from '@vueuse/core'
    import { RiCheckLine } from '@remixicon/vue'
    import {
        DropdownMenuItemIndicator,
        DropdownMenuRadioItem,
        useForwardPropsEmits,
    } from 'reka-ui'
    import { cn } from '@/lib/utils'

    const props = defineProps<DropdownMenuRadioItemProps & { class?: HTMLAttributes['class'] }>()

    const emits = defineEmits<DropdownMenuRadioItemEmits>()

    const delegatedProps = reactiveOmit(props, 'class')

    const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DropdownMenuRadioItem data-slot="dropdown-menu-radio-item" v-bind="forwarded" :class="cn(
        'relative flex cursor-default select-none items-center rounded-sm py-1.5 pr-8 pl-1.5 text-sm outline-none transition-colors focus:bg-accent focus:text-accent-foreground focus:**:text-accent-foreground gap-1.5 data-inset:pl-8 [&_svg:not([class*=size-])]:size-4 data-disabled:pointer-events-none data-disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0',
        props.class,
    )">
        <span class="absolute right-2 flex items-center justify-center pointer-events-none" data-slot="dropdown-menu-radio-item-indicator">
            <DropdownMenuItemIndicator>
                <slot name="indicator-icon">
                    <RiCheckLine />
                </slot>
            </DropdownMenuItemIndicator>
        </span>
        <slot />
    </DropdownMenuRadioItem>
</template>
