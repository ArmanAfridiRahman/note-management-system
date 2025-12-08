<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';
import { Check, ChevronDown, X, Search, Plus, Loader2 } from 'lucide-vue-next';

interface Option {
    value: string | number;
    label: string;
    description?: string;
    disabled?: boolean;
    avatar?: string;
    color?: string;
}

interface Props {
    modelValue?: string | number | (string | number)[];
    options: Option[];
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    error?: string;
    multiple?: boolean;
    class?: string;
    creatable?: boolean;
    createLabel?: string;
    creating?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    disabled: false,
    multiple: false,
    creatable: false,
    createLabel: 'Create',
    creating: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | (string | number)[]];
    'create': [value: string];
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLDivElement | null>(null);

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(
        opt =>
            opt.label.toLowerCase().includes(query) ||
            opt.description?.toLowerCase().includes(query)
    );
});

// Show create option when creatable is enabled and search query doesn't exactly match any option
const showCreateOption = computed(() => {
    if (!props.creatable || !searchQuery.value.trim()) return false;
    const query = searchQuery.value.trim().toLowerCase();
    return !props.options.some(opt => opt.label.toLowerCase() === query);
});

function handleCreate() {
    if (!searchQuery.value.trim()) return;
    emit('create', searchQuery.value.trim());
}

function clearSearch() {
    searchQuery.value = '';
}

// Expose methods for parent component
defineExpose({
    clearSearch,
});

const selectedOptions = computed(() => {
    if (props.multiple) {
        const values = props.modelValue as (string | number)[] || [];
        return props.options.filter(opt => values.includes(opt.value));
    }
    return props.options.filter(opt => opt.value === props.modelValue);
});

const displayValue = computed(() => {
    if (props.multiple) {
        const selected = selectedOptions.value;
        if (selected.length === 0) return props.placeholder;
        if (selected.length === 1) return selected[0].label;
        return `${selected.length} selected`;
    }
    return selectedOptions.value[0]?.label || props.placeholder;
});

function isSelected(value: string | number): boolean {
    if (props.multiple) {
        return ((props.modelValue as (string | number)[]) || []).includes(value);
    }
    return props.modelValue === value;
}

function toggleOption(option: Option) {
    if (option.disabled) return;

    if (props.multiple) {
        const currentValues = (props.modelValue as (string | number)[]) || [];
        const newValues = isSelected(option.value)
            ? currentValues.filter(v => v !== option.value)
            : [...currentValues, option.value];
        emit('update:modelValue', newValues);
    } else {
        emit('update:modelValue', option.value);
        isOpen.value = false;
    }
}

function removeOption(value: string | number, event: Event) {
    event.stopPropagation();
    if (props.multiple) {
        const currentValues = (props.modelValue as (string | number)[]) || [];
        emit('update:modelValue', currentValues.filter(v => v !== value));
    }
}

function clearAll(event: Event) {
    event.stopPropagation();
    emit('update:modelValue', props.multiple ? [] : '');
}

function handleClickOutside(event: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
}

watch(isOpen, (open) => {
    if (open) {
        searchQuery.value = '';
    }
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Trigger -->
        <button
            type="button"
            :disabled="disabled"
            :class="cn(
                'flex min-h-10 w-full items-center justify-between rounded-md border bg-background px-3 py-2 text-sm transition-colors',
                'outline-none ring-0 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0',
                'focus:border-primary/50',
                'disabled:cursor-not-allowed disabled:opacity-50',
                error ? 'border-destructive' : 'border-input',
                props.class
            )"
            @click="isOpen = !isOpen"
        >
            <div class="flex flex-1 flex-wrap gap-1">
                <template v-if="multiple && selectedOptions.length > 0">
                    <span
                        v-for="opt in selectedOptions.slice(0, 3)"
                        :key="opt.value"
                        class="inline-flex items-center gap-1 rounded bg-muted px-2 py-0.5 text-xs"
                    >
                        <span
                            v-if="opt.color"
                            class="h-2 w-2 rounded-full"
                            :style="{ backgroundColor: opt.color }"
                        />
                        {{ opt.label }}
                        <X
                            class="h-3 w-3 cursor-pointer hover:text-destructive"
                            @click="removeOption(opt.value, $event)"
                        />
                    </span>
                    <span v-if="selectedOptions.length > 3" class="text-xs text-muted-foreground">
                        +{{ selectedOptions.length - 3 }} more
                    </span>
                </template>
                <span v-else :class="selectedOptions.length ? 'text-foreground' : 'text-muted-foreground'">
                    {{ displayValue }}
                </span>
            </div>
            <div class="flex items-center gap-1">
                <X
                    v-if="(multiple ? (modelValue as (string | number)[])?.length : modelValue)"
                    class="h-4 w-4 text-muted-foreground hover:text-foreground"
                    @click="clearAll"
                />
                <ChevronDown
                    :class="cn('h-4 w-4 text-muted-foreground transition-transform', isOpen && 'rotate-180')"
                />
            </div>
        </button>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full rounded-md border border-border bg-background shadow-lg"
        >
            <!-- Search -->
            <div class="border-b border-border p-2">
                <div class="relative">
                    <Search class="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full rounded-md border border-input bg-background py-1.5 pl-8 pr-3 text-sm outline-none ring-0 focus:outline-none focus:ring-0 focus:border-primary/50"
                        @click.stop
                    />
                </div>
            </div>

            <!-- Options -->
            <div class="max-h-60 overflow-y-auto p-1">
                <!-- Create New Option -->
                <button
                    v-if="showCreateOption"
                    type="button"
                    :disabled="creating"
                    :class="cn(
                        'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none',
                        'hover:bg-primary/10 focus:bg-primary/10 text-primary font-medium',
                        'border-b border-border mb-1 pb-2',
                        creating && 'cursor-not-allowed opacity-50'
                    )"
                    @click="handleCreate"
                >
                    <div class="flex h-4 w-4 items-center justify-center">
                        <Loader2 v-if="creating" class="h-3.5 w-3.5 animate-spin" />
                        <Plus v-else class="h-3.5 w-3.5" />
                    </div>
                    <span>{{ createLabel }} "{{ searchQuery.trim() }}"</span>
                </button>

                <div
                    v-if="filteredOptions.length === 0 && !showCreateOption"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No results found
                </div>
                <button
                    v-for="option in filteredOptions"
                    :key="option.value"
                    type="button"
                    :disabled="option.disabled"
                    :class="cn(
                        'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none',
                        'hover:bg-muted focus:bg-muted',
                        option.disabled && 'cursor-not-allowed opacity-50'
                    )"
                    @click="toggleOption(option)"
                >
                    <div
                        :class="cn(
                            'flex h-4 w-4 items-center justify-center rounded border',
                            isSelected(option.value)
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-input'
                        )"
                    >
                        <Check v-if="isSelected(option.value)" class="h-3 w-3" />
                    </div>
                    <span
                        v-if="option.avatar"
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium"
                    >
                        {{ option.avatar }}
                    </span>
                    <span
                        v-if="option.color"
                        class="h-3 w-3 rounded-full"
                        :style="{ backgroundColor: option.color }"
                    />
                    <div class="flex flex-col items-start">
                        <span>{{ option.label }}</span>
                        <span v-if="option.description" class="text-xs text-muted-foreground">
                            {{ option.description }}
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <p v-if="error" class="mt-1.5 text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>
