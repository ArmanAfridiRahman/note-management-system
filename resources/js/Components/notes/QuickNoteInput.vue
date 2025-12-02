<script setup lang="ts">
import { ref, computed } from 'vue';
import { cn } from '@/lib/utils';
import { Plus, Image, Palette, Pin, Lock, Check } from 'lucide-vue-next';

interface Props {
    class?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    click: [];
    quickCreate: [data: { title: string; is_pinned: boolean; color: string }];
}>();

const isExpanded = ref(false);
const title = ref('');
const isPinned = ref(false);
const selectedColor = ref('');
const showColorPicker = ref(false);

const colorOptions = [
    { value: '', label: 'None' },
    { value: '#fef3c7', label: 'Yellow' },
    { value: '#dcfce7', label: 'Green' },
    { value: '#dbeafe', label: 'Blue' },
    { value: '#fce7f3', label: 'Pink' },
    { value: '#f3e8ff', label: 'Purple' },
    { value: '#fed7aa', label: 'Orange' },
    { value: '#e5e7eb', label: 'Gray' },
];

const containerStyle = computed(() => {
    if (selectedColor.value) {
        return { backgroundColor: selectedColor.value };
    }
    return {};
});

function handleFocus() {
    isExpanded.value = true;
}

function handleClick() {
    emit('click');
}

function handleQuickSave() {
    if (title.value.trim()) {
        emit('quickCreate', {
            title: title.value.trim(),
            is_pinned: isPinned.value,
            color: selectedColor.value,
        });
        resetForm();
    }
}

function resetForm() {
    title.value = '';
    isPinned.value = false;
    selectedColor.value = '';
    showColorPicker.value = false;
    isExpanded.value = false;
}

function selectColor(color: string) {
    selectedColor.value = color;
    showColorPicker.value = false;
}
</script>

<template>
    <div
        :class="cn(
            'mx-auto w-full max-w-xl cursor-pointer rounded-lg border border-border bg-background shadow-md transition-all hover:shadow-lg',
            props.class
        )"
        :style="containerStyle"
        @click="handleClick"
    >
        <div class="flex items-center gap-3 px-4 py-3">
            <Plus class="h-5 w-5 text-muted-foreground" />
            <span class="flex-1 text-muted-foreground">Take a note...</span>
        </div>
    </div>
</template>
