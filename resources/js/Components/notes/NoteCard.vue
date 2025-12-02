<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Card, Badge, Button, Dropdown, DropdownItem } from '@/Components/ui';
import { formatRelativeTime, cn } from '@/lib/utils';
import { Pin, Lock, Star, MoreVertical, Edit, Share2, Archive, Trash2 } from 'lucide-vue-next';

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface Group {
    id: number;
    name: string;
    slug: string;
    color?: string;
}

interface Note {
    id: number;
    title: string;
    slug: string;
    excerpt?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    created_at: string;
    updated_at: string;
    group?: Group;
    tags: Tag[];
}

interface Props {
    note: Note;
    compact?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
});

const emit = defineEmits<{
    view: [note: Note];
    edit: [note: Note];
    delete: [note: Note];
    archive: [note: Note];
    share: [note: Note];
    togglePin: [note: Note];
    toggleFavorite: [note: Note];
}>();

const formattedDate = computed(() => formatRelativeTime(props.note.updated_at));

const cardStyle = computed(() => {
    if (props.note.color) {
        return { borderLeftColor: props.note.color, borderLeftWidth: '3px' };
    }
    return {};
});
</script>

<template>
    <Card
        variant="outlined"
        hoverable
        :padding="compact ? 'sm' : 'md'"
        :class="cn('group relative', props.class)"
        :style="cardStyle"
    >
        <div class="flex items-start justify-between gap-3">
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
                        @click="emit('view', note)"
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
