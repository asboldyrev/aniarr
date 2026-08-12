<template>
    <div class="relative">
        <Input
            :id="id"
            :model-value="modelValue"
            :type="visible ? 'text' : 'password'"
            :placeholder="placeholder"
            class="pr-10"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <Button
            type="button"
            variant="ghost"
            size="icon"
            class="absolute right-0 top-0 h-full w-10 text-muted-foreground hover:text-foreground"
            :aria-label="visible ? 'Скрыть значение' : 'Показать значение'"
            @click="visible = ! visible"
        >
            <EyeOff v-if="visible" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
        </Button>
    </div>
</template>

<script setup>
    import { ref } from 'vue'
    import { Eye, EyeOff } from '@lucide/vue'
    import Button from '@/components/ui/button/Button.vue'
    import Input from '@/components/ui/input/Input.vue'

    defineProps({
        id: { type: String, required: true },
        modelValue: { type: [String, null], default: '' },
        placeholder: { type: String, default: '' },
    })

    defineEmits(['update:modelValue'])

    const visible = ref(false)
</script>
