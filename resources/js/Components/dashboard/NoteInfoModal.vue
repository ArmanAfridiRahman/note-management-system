<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, Badge } from '@/Components/ui';
import { FileText, Calendar, Archive, Lock, Eye, Folder, Pin, Star } from 'lucide-vue-next';
import type { NoteData } from '@/types/models';

interface Props {
    open: boolean;
    note: NoteData | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Dialog
        :open="open"
        title="Note Information"
        size="md"
        @update:open="emit('update:open', $event)"
    >
        <div v-if="note" class="space-y-4">
            <!-- Note Title -->
            <div class="flex items-start gap-3 pb-4 border-b border-border">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 shrink-0">
                    <FileText class="h-5 w-5 text-primary" />
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-foreground truncate">{{ note.title }}</h3>
                    <p v-if="note.excerpt" class="text-sm text-muted-foreground line-clamp-2 mt-1">
                        {{ note.excerpt }}
                    </p>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="flex flex-wrap gap-2">
                <Badge v-if="note.is_pinned" variant="secondary" class="gap-1">
                    <Pin class="h-3 w-3" />
                    Pinned
                </Badge>
                <Badge v-if="note.is_favorited" class="gap-1 bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    <Star class="h-3 w-3" />
                    Favorite
                </Badge>
                <Badge v-if="note.is_encrypted" class="gap-1 bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
                    <Lock class="h-3 w-3" />
                    Encrypted
                </Badge>
                <Badge v-if="note.is_archived" class="gap-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
                    <Archive class="h-3 w-3" />
                    Archived
                </Badge>
            </div>

            <!-- Info Grid -->
            <div class="grid gap-3">
                <div class="flex items-center justify-between py-2 border-b border-border/50">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Calendar class="h-4 w-4" />
                        Created
                    </span>
                    <span class="text-sm font-medium">{{ formatDate(note.created_at) }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-border/50">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Calendar class="h-4 w-4" />
                        Last Updated
                    </span>
                    <span class="text-sm font-medium">{{ formatDate(note.updated_at) }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-border/50">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Eye class="h-4 w-4" />
                        Times Opened
                    </span>
                    <span class="text-sm font-medium">{{ note.open_count ?? 0 }}</span>
                </div>

                <div v-if="note.is_archived && note.archived_at" class="flex items-center justify-between py-2 border-b border-border/50">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Archive class="h-4 w-4" />
                        Archived On
                    </span>
                    <span class="text-sm font-medium">{{ formatDate(note.archived_at) }}</span>
                </div>

                <div v-if="note.is_encrypted && note.encryption_hint" class="flex items-center justify-between py-2 border-b border-border/50">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Lock class="h-4 w-4" />
                        Encryption Hint
                    </span>
                    <span class="text-sm font-medium italic">"{{ note.encryption_hint }}"</span>
                </div>

                <div v-if="note.group" class="flex items-center justify-between py-2">
                    <span class="text-sm text-muted-foreground flex items-center gap-2">
                        <Folder class="h-4 w-4" />
                        Group
                    </span>
                    <Badge variant="secondary">{{ note.group.name }}</Badge>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="note.tags && note.tags.length > 0" class="pt-2">
                <p class="text-xs text-muted-foreground mb-2">Tags</p>
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="tag in note.tags"
                        :key="tag.id"
                        :color="tag.color"
                        size="sm"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </div>
        </div>
    </Dialog>
</template>
