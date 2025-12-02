<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Button, Dialog } from '@/Components/ui';
import { NoteList } from '@/Components/notes';
import { ArrowLeft, Archive } from 'lucide-vue-next';

interface TagData {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface GroupData {
    id: number;
    name: string;
    slug: string;
    color?: string;
}

interface NoteData {
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
    group?: GroupData;
    tags: TagData[];
}

interface Props {
    notes: {
        data: NoteData[];
        next_cursor?: string;
        prev_cursor?: string;
    };
}

const props = defineProps<Props>();

const noteListRef = ref<InstanceType<typeof NoteList> | null>(null);
const deleteModal = ref(false);
const noteToDelete = ref<NoteData | null>(null);

const handleView = (note: NoteData) => {
    router.visit(`/notes/${note.id}`);
};

const handleEdit = (note: NoteData) => {
    router.visit(`/notes/${note.id}/edit`);
};

const handleDelete = (note: NoteData) => {
    noteToDelete.value = note;
    deleteModal.value = true;
};

const confirmDelete = () => {
    if (noteToDelete.value) {
        router.delete(`/notes/${noteToDelete.value.id}`, {
            onSuccess: () => {
                deleteModal.value = false;
                noteToDelete.value = null;
                noteListRef.value?.refresh();
            },
        });
    }
};

const handleUnarchive = (note: NoteData) => {
    router.patch(`/notes/${note.id}/archive`, {}, {
        preserveScroll: true,
        onSuccess: () => noteListRef.value?.refresh(),
    });
};

const handleTogglePin = (note: NoteData) => {
    router.patch(`/notes/${note.id}/pin`, {}, {
        preserveScroll: true,
        onSuccess: () => noteListRef.value?.refresh(),
    });
};

const handleToggleFavorite = (note: NoteData) => {
    router.patch(`/notes/${note.id}/favorite`, {}, {
        preserveScroll: true,
        onSuccess: () => noteListRef.value?.refresh(),
    });
};

const goBack = () => {
    router.visit('/notes');
};
</script>

<template>
    <Head title="Archived Notes" />

    <AppLayout title="Archived Notes">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <Button variant="ghost" @click="goBack">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Notes
            </Button>
        </div>

        <!-- Archive Info -->
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-yellow-500/20 bg-yellow-500/5 p-4">
            <Archive class="h-5 w-5 text-yellow-600" />
            <div>
                <p class="font-medium text-yellow-600 dark:text-yellow-400">Archived Notes</p>
                <p class="text-sm text-muted-foreground">
                    These notes have been archived. You can unarchive them to move them back to your active notes.
                </p>
            </div>
        </div>

        <!-- Notes List -->
        <NoteList
            ref="noteListRef"
            fetch-url="/api/notes"
            :initial-notes="notes.data"
            :filters="{ is_archived: true }"
            empty-icon="📦"
            empty-title="No archived notes"
            empty-description="Notes you archive will appear here."
            :grid-cols="2"
            @view="handleView"
            @edit="handleEdit"
            @delete="handleDelete"
            @archive="handleUnarchive"
            @toggle-pin="handleTogglePin"
            @toggle-favorite="handleToggleFavorite"
        />

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal" title="Delete Note">
            <p class="text-muted-foreground">
                Are you sure you want to delete "{{ noteToDelete?.title }}"?
                This action cannot be undone.
            </p>
            <template #footer>
                <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                <Button variant="destructive" @click="confirmDelete">Delete</Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
