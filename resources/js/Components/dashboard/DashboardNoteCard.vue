<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Card, Badge, Button } from '@/Components/ui';
import { Info, Pin, Star, Lock } from 'lucide-vue-next';
import type { NoteData } from '@/types/models';

interface Props {
    note: NoteData;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'show-info': [note: NoteData];
}>();

const cardStyle = computed(() => {
    if (props.note.color) {
        return {
            borderLeftColor: props.note.color,
            borderLeftWidth: '3px',
        };
    }
    return {};
});
</script>

<template>
    <Card
        variant="outlined"
        padding="sm"
        class="group hover:border-primary/30 transition-colors"
        :style="cardStyle"
    >
        <div class="flex items-start justify-between gap-2">
            <Link :href="`/notes/${note.id}`" class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 mb-1">
                    <Pin v-if="note.is_pinned" class="h-3 w-3 text-blue-500 shrink-0" />
                    <Star v-if="note.is_favorited" class="h-3 w-3 text-amber-500 shrink-0" />
                    <Lock v-if="note.is_encrypted" class="h-3 w-3 text-green-500 shrink-0" />
                    <h4 class="font-medium text-sm text-foreground truncate">
                        {{ note.title }}
                    </h4>
                </div>
                <p v-if="note.excerpt" class="text-xs text-muted-foreground line-clamp-2">
                    {{ note.excerpt }}
                </p>
            </Link>
            <Button
                variant="ghost"
                size="icon"
                class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                @click.stop="emit('show-info', note)"
            >
                <Info class="h-3.5 w-3.5" />
            </Button>
        </div>

        <div v-if="note.tags && note.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
            <Badge
                v-for="tag in note.tags.slice(0, 3)"
                :key="tag.id"
                :color="tag.color"
                size="sm"
            >
                {{ tag.name }}
            </Badge>
            <Badge v-if="note.tags.length > 3" size="sm" variant="secondary">
                +{{ note.tags.length - 3 }}
            </Badge>
        </div>
    </Card>
</template>
