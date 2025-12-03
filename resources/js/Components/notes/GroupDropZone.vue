<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card } from '@/Components/ui';
import { Folder, FolderOpen, Plus } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import type { GroupData } from '@/types/models';

interface Props {
    group: GroupData;
    notesCount?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    notesCount: 0,
});

const emit = defineEmits<{
    drop: [groupId: number, noteId: number];
    click: [group: GroupData];
}>();

const isDragOver = ref(false);
const isValidDrop = ref(false);

const handleDragEnter = (event: DragEvent) => {
    event.preventDefault();
    isDragOver.value = true;

    // Check if this is a valid note drop
    if (event.dataTransfer?.types.includes('application/json')) {
        isValidDrop.value = true;
        event.dataTransfer.dropEffect = 'move';
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
};

const handleDragLeave = (event: DragEvent) => {
    // Only reset if we're leaving the element entirely
    const target = event.currentTarget as HTMLElement;
    const related = event.relatedTarget as HTMLElement;

    if (!target.contains(related)) {
        isDragOver.value = false;
        isValidDrop.value = false;
    }
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragOver.value = false;
    isValidDrop.value = false;

    const data = event.dataTransfer?.getData('application/json');
    if (!data) return;

    try {
        const parsed = JSON.parse(data);
        if (parsed.type === 'note' && parsed.noteId) {
            emit('drop', props.group.id, parsed.noteId);
        }
    } catch (e) {
        console.error('Failed to parse drop data:', e);
    }
};

const cardClasses = computed(() => cn(
    'relative overflow-hidden transition-all duration-200',
    isDragOver.value && isValidDrop.value && 'ring-2 ring-primary ring-offset-2 bg-primary/5 scale-[1.02]',
    isDragOver.value && !isValidDrop.value && 'ring-2 ring-destructive ring-offset-2',
    props.class
));
</script>

<template>
    <Card
        variant="outlined"
        hoverable
        padding="md"
        :class="cardClasses"
        @dragenter="handleDragEnter"
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
        @click="emit('click', group)"
    >
        <div class="flex items-center gap-3">
            <!-- Folder Icon -->
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-colors"
                :class="isDragOver ? 'bg-primary/20' : 'bg-muted'"
                :style="group.color ? { backgroundColor: `${group.color}20` } : {}"
            >
                <FolderOpen
                    v-if="isDragOver"
                    class="h-5 w-5 text-primary"
                    :style="group.color ? { color: group.color } : {}"
                />
                <Folder
                    v-else
                    class="h-5 w-5 text-muted-foreground"
                    :style="group.color ? { color: group.color } : {}"
                />
            </div>

            <!-- Group Info -->
            <div class="flex-1 min-w-0">
                <h3 class="font-medium text-foreground truncate">
                    {{ group.name }}
                </h3>
                <p class="text-xs text-muted-foreground">
                    {{ notesCount }} {{ notesCount === 1 ? 'note' : 'notes' }}
                </p>
            </div>

            <!-- Drop Indicator -->
            <div
                v-if="isDragOver && isValidDrop"
                class="absolute inset-0 flex items-center justify-center bg-primary/10 rounded-lg"
            >
                <div class="flex items-center gap-2 text-primary font-medium">
                    <Plus class="h-5 w-5" />
                    <span>Drop to add</span>
                </div>
            </div>
        </div>

        <!-- Children Groups -->
        <div v-if="group.children?.length" class="mt-3 pl-10 space-y-2">
            <GroupDropZone
                v-for="child in group.children"
                :key="child.id"
                :group="child"
                :notes-count="0"
                @drop="(groupId, noteId) => emit('drop', groupId, noteId)"
                @click="(g) => emit('click', g)"
            />
        </div>
    </Card>
</template>
