<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, Badge, Button, Dropdown, DropdownItem } from '@/Components/ui';
import { formatRelativeTime, cn } from '@/lib/utils';
import { Pin, Lock, Star, MoreVertical, Edit, Share2, Archive, Trash2, GripVertical } from 'lucide-vue-next';
import type { NoteData } from '@/types/models';

interface Props {
    note: NoteData;
    compact?: boolean;
    draggable?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    draggable: true,
});

const emit = defineEmits<{
    view: [note: NoteData];
    edit: [note: NoteData];
    delete: [note: NoteData];
    archive: [note: NoteData];
    share: [note: NoteData];
    togglePin: [note: NoteData];
    toggleFavorite: [note: NoteData];
    dragStart: [note: NoteData, event: DragEvent];
    dragEnd: [note: NoteData, event: DragEvent];
}>();

const isDragging = ref(false);

const formattedDate = computed(() => formatRelativeTime(props.note.updated_at));

const cardStyle = computed(() => {
    if (props.note.color) {
        return { borderLeftColor: props.note.color, borderLeftWidth: '3px' };
    }
    return {};
});

const handleDragStart = (event: DragEvent) => {
    if (!props.draggable) return;

    isDragging.value = true;

    // Set drag data
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('application/json', JSON.stringify({
            type: 'note',
            noteId: props.note.id,
            noteTitle: props.note.title,
        }));

        // Add visual feedback
        event.dataTransfer.setData('text/plain', props.note.title);
    }

    emit('dragStart', props.note, event);
};

const handleDragEnd = (event: DragEvent) => {
    isDragging.value = false;
    emit('dragEnd', props.note, event);
};
</script>

<template>
    <Card
        variant="outlined"
        hoverable
        :padding="compact ? 'sm' : 'md'"
        :class="cn(
            'group relative transition-all duration-200',
            isDragging && 'opacity-50 scale-95 ring-2 ring-primary',
            draggable && 'cursor-grab active:cursor-grabbing',
            props.class
        )"
        :style="cardStyle"
        :draggable="draggable"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
    >
        <div class="flex items-start justify-between gap-3">
            <!-- Drag Handle -->
            <div
                v-if="draggable"
                class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing touch-none"
            >
                <GripVertical class="h-5 w-5 text-muted-foreground" />
            </div>

            <div class="flex-1 min-w-0">
                <!-- Title Row -->
                <div class="flex items-center gap-2">
                    <Pin
                        v-if="note.is_pinned"
                        class="h-4 w-4 shrink-0 text-accent"
                    />
                    <Lock
                        v-if="note.is_encrypted"
                        class="h-4 w-4 shrink-0 text-yellow-500"
                    />
                    <Star
                        v-if="note.is_favorited"
                        class="h-4 w-4 shrink-0 fill-yellow-400 text-yellow-400"
                    />
                    <h3
                        class="cursor-pointer truncate font-medium text-foreground hover:text-primary transition-colors"
                        @click.stop="emit('view', note)"
                    >
                        {{ note.title }}
                    </h3>
                </div>

                <!-- Excerpt -->
                <p
                    v-if="!compact && note.excerpt"
                    class="mt-1.5 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ note.is_encrypted ? 'This note is encrypted' : note.excerpt }}
                </p>

                <!-- Tags -->
                <div v-if="note.tags.length" class="mt-2 flex flex-wrap gap-1">
                    <Badge
                        v-for="tag in note.tags.slice(0, 3)"
                        :key="tag.id"
                        :color="tag.color"
                        size="sm"
                    >
                        {{ tag.name }}
                    </Badge>
                    <Badge v-if="note.tags.length > 3" variant="secondary" size="sm">
                        +{{ note.tags.length - 3 }}
                    </Badge>
                </div>
            </div>

            <!-- Actions Dropdown -->
            <Dropdown align="right">
                <template #trigger>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                        @click.stop
                    >
                        <MoreVertical class="h-4 w-4" />
                    </Button>
                </template>

                <template #default="{ close }">
                    <DropdownItem @click="emit('edit', note); close()">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit
                    </DropdownItem>
                    <DropdownItem @click="emit('share', note); close()">
                        <Share2 class="mr-2 h-4 w-4" />
                        Share
                    </DropdownItem>
                    <DropdownItem @click="emit('togglePin', note); close()">
                        <Pin class="mr-2 h-4 w-4" />
                        {{ note.is_pinned ? 'Unpin' : 'Pin' }}
                    </DropdownItem>
                    <DropdownItem @click="emit('toggleFavorite', note); close()">
                        <Star class="mr-2 h-4 w-4" />
                        {{ note.is_favorited ? 'Unfavorite' : 'Favorite' }}
                    </DropdownItem>
                    <DropdownItem @click="emit('archive', note); close()">
                        <Archive class="mr-2 h-4 w-4" />
                        {{ note.is_archived ? 'Unarchive' : 'Archive' }}
                    </DropdownItem>
                    <DropdownItem destructive @click="emit('delete', note); close()">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </DropdownItem>
                </template>
            </Dropdown>
        </div>

        <!-- Footer -->
        <div class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
            <span v-if="note.group" class="flex items-center gap-1">
                <span
                    v-if="note.group.color"
                    class="h-2 w-2 rounded-full"
                    :style="{ backgroundColor: note.group.color }"
                />
                {{ note.group.name }}
            </span>
            <span v-else />
            <span>{{ formattedDate }}</span>
        </div>
    </Card>
</template>
