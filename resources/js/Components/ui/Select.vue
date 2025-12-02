<script setup lang="ts">
import { computed, useSlots } from 'vue';
import { cn } from '@/lib/utils';

interface Option {
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface Props {
    modelValue?: string | number;
    options?: Option[];
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    options: () => [],
    placeholder: '',
    disabled: false,
});

const slots = useSlots();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const hasSlotContent = computed(() => !!slots.default);

const selectClasses = computed(() =>
    cn(
        'flex h-10 w-full items-center justify-between rounded-md border bg-background px-3 py-2 text-sm ring-offset-background',
        'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        props.error ? 'border-destructive' : 'border-input',
        props.class
    )
);

function onChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="w-full">
        <select
            :value="modelValue"
            :disabled="disabled"
            :class="selectClasses"
            @change="onChange"
        >
            <option v-if="placeholder" value="" disabled>
                {{ placeholder }}
            </option>
            <!-- Slot-based options -->
            <slot v-if="hasSlotContent" />
            <!-- Props-based options -->
            <template v-else>
                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                    :disabled="option.disabled"
                >
                    {{ option.label }}
                </option>
            </template>
        </select>
        <p v-if="error" class="mt-1.5 text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>
