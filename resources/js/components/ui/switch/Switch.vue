<template>
    <SwitchRoot
        :model-value="modelValue"
        :disabled="disabled"
        :class="cn(
            'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border border-transparent shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
            props.class,
        )"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <SwitchThumb class="pointer-events-none block h-4 w-4 rounded-full bg-background shadow-sm transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0" />
    </SwitchRoot>
</template>

<script setup>
    import { SwitchRoot, SwitchThumb } from 'reka-ui'
    import { cn } from '@/lib/utils'

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        class: { type: String, default: '' },
    })

    defineEmits(['update:modelValue'])
</script>
