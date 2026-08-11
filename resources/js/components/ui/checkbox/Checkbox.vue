<template>
    <CheckboxRoot
        :model-value="modelValue"
        :disabled="disabled"
        :class="cn(
            'peer h-4 w-4 shrink-0 rounded border border-input bg-background shadow-xs outline-none transition-shadow focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
            props.class,
        )"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <CheckboxIndicator class="flex items-center justify-center text-current">
            <Check class="h-3.5 w-3.5" />
        </CheckboxIndicator>
    </CheckboxRoot>
</template>

<script setup>
    import { Check } from '@lucide/vue'
    import { CheckboxIndicator, CheckboxRoot } from 'reka-ui'
    import { cn } from '@/lib/utils'

    const props = defineProps({
        modelValue: { type: [Boolean, String], default: false },
        disabled: { type: Boolean, default: false },
        class: { type: String, default: '' },
    })

    defineEmits(['update:modelValue'])
</script>
