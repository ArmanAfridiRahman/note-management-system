<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardContent, Button, Input, Badge, Select, Dialog, ComboBox } from '@/Components/ui';
import { NoteList, NoteModal, QuickNoteInput } from '@/Components/notes';
import { Search, Filter, X, Tag, Folder, Users } from 'lucide-vue-next';
import { useDebounce } from '@/Composables/useDebounce';
import type { NoteData, TagData, GroupData, UserData } from '@/types/models';

interface Props {
    notes: {
        data: NoteData[];
        next_cursor?: string;
        prev_cursor?: string;
    };
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
    filters: {
        filter?: string;
        tag_id?: number;
        group_id?: number;
    };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const debouncedSearch = useDebounce(searchQuery, 300);
const showFilters = ref(false);
const selectedFilter = ref(props.filters.filter || '');
const selectedTagId = ref(props.filters.tag_id?.toString() || '');
const selectedGroupId = ref(props.filters.group_id?.toString() || '');
const noteListRef = ref<InstanceType<typeof NoteList> | null>(null);

// Modal states
const noteModalOpen = ref(false);
const noteModalMode = ref<'view' | 'edit' | 'create'>('create');
const selectedNote = ref<NoteData | null>(null);
const deleteModal = ref(false);
const noteToDelete = ref<NoteData | null>(null);

// Share modal
const shareModal = ref(false);
const shareNote = ref<NoteData | null>(null);
const shareUserIds = ref<number[]>([]);

const filterOptions = [
    { value: '', label: 'All Notes' },
    { value: 'favorites', label: 'Favorites' },
    { value: 'encrypted', label: 'Encrypted' },
    { value: 'pinned', label: 'Pinned' },
];

const apiFilters = computed(() => ({
    filter: selectedFilter.value || undefined,
    tag_id: selectedTagId.value || undefined,
    group_id: selectedGroupId.value || undefined,
    q: debouncedSearch.value || undefined,
}));

const hasActiveFilters = computed(() => {
    return selectedFilter.value || selectedTagId.value || selectedGroupId.value;
});

const tagOptions = computed(() =>
    props.tags.map((tag) => ({
        value: tag.id.toString(),
        label: tag.name,
        color: tag.color,
    }))
);

const groupOptions = computed(() =>
    props.groups.map((group) => ({
        value: group.id.toString(),
        label: group.name,
        color: group.color,
    }))
);

const userOptions = computed(() =>
    (props.users || []).map((user) => ({
        value: user.id,
        label: user.name,
        description: user.email,
        avatar: user.name.charAt(0).toUpperCase(),
    }))
);

const clearFilters = () => {
    selectedFilter.value = '';
    selectedTagId.value = '';
    selectedGroupId.value = '';
};

// Note actions
const handleView = (note: NoteData) => {
    selectedNote.value = note;
    noteModalMode.value = 'view';
    noteModalOpen.value = true;
};

const handleEdit = (note: NoteData) => {
    selectedNote.value = note;
    noteModalMode.value = 'edit';
    noteModalOpen.value = true;
};

const handleCreateNew = () => {
    selectedNote.value = null;
    noteModalMode.value = 'create';
    noteModalOpen.value = true;
};

const handleDelete = (note: NoteData) => {
    noteToDelete.value = note;
    deleteModal.value = true;
};

const confirmDelete = () => {
    if (noteToDelete.value) {
        router.delete(`/notes/${noteToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModal.value = false;
                noteToDelete.value = null;
                noteListRef.value?.refresh();
            },
        });
    }
};

const handleArchive = (note: NoteData) => {
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

const handleShare = (note: NoteData | { id?: number; title: string }) => {
    if (note.id) {
        shareNote.value = note as NoteData;
        shareUserIds.value = [];
        shareModal.value = true;
    }
};

const confirmShare = () => {
    if (shareNote.value && shareUserIds.value.length > 0) {
        router.post('/api/shares', {
            note_id: shareNote.value.id,
            user_ids: shareUserIds.value,
            permission: 'view',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                shareModal.value = false;
                shareNote.value = null;
                shareUserIds.value = [];
            },
        });
    }
};

const handleNoteSaved = () => {
    noteListRef.value?.refresh();
};

const handleNoteDeleted = () => {
    noteListRef.value?.refresh();
};
</script>

<template>
    <Head title="Notes" />

    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Quick Note Input (Google Keep style) -->
            <QuickNoteInput
                class="mb-8"
                @click="handleCreateNew"
            />

            <!-- Filters Section -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-3">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search notes..."
                            class="pl-9"
                        />
                    </div>

                    <!-- Filter Toggle -->
                    <Button
                        variant="outline"
                        size="sm"
                        :class="{ 'border-primary text-primary': hasActiveFilters }"
                        @click="showFilters = !showFilters"
                    >
                        <Filter class="mr-2 h-4 w-4" />
                        Filters
                        <Badge v-if="hasActiveFilters" size="sm" class="ml-2">
                            {{ [selectedFilter, selectedTagId, selectedGroupId].filter(Boolean).length }}
                        </Badge>
                    </Button>
                </div>
            </div>

            <!-- Filters Panel -->
            <Card v-if="showFilters" variant="outlined" class="mb-6">
                <CardContent class="p-4">
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- Filter Type -->
                        <div class="min-w-[150px]">
                            <label class="mb-1.5 block text-sm font-medium">Filter</label>
                            <Select v-model="selectedFilter">
                                <option v-for="opt in filterOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </Select>
                        </div>

                        <!-- Tag Filter -->
                        <div class="min-w-[180px]">
                            <label class="mb-1.5 block text-sm font-medium">
                                <Tag class="mr-1 inline h-3 w-3" />
                                Tag
                            </label>
                            <ComboBox
                                v-model="selectedTagId"
                                :options="[{ value: '', label: 'All Tags' }, ...tagOptions]"
                                placeholder="Select tag..."
                            />
                        </div>

                        <!-- Group Filter -->
                        <div class="min-w-[180px]">
                            <label class="mb-1.5 block text-sm font-medium">
                                <Folder class="mr-1 inline h-3 w-3" />
                                Group
                            </label>
                            <ComboBox
                                v-model="selectedGroupId"
                                :options="[{ value: '', label: 'All Groups' }, ...groupOptions]"
                                placeholder="Select group..."
                            />
                        </div>

                        <!-- Clear Filters -->
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="sm"
                            @click="clearFilters"
                        >
                            <X class="mr-1 h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Notes Grid -->
            <NoteList
                ref="noteListRef"
                fetch-url="/api/notes"
                :initial-notes="notes.data"
                :filters="apiFilters"
                empty-title="No notes found"
                empty-description="Create your first note by clicking the input above."
                :grid-cols="3"
                @view="handleView"
                @edit="handleEdit"
                @delete="handleDelete"
                @archive="handleArchive"
                @share="handleShare"
                @toggle-pin="handleTogglePin"
                @toggle-favorite="handleToggleFavorite"
                @create-new="handleCreateNew"
            />
        </div>

        <!-- Note Modal (View/Edit/Create) -->
        <NoteModal
            v-model:open="noteModalOpen"
            :note="selectedNote"
            :tags="tags"
            :groups="groups"
            :mode="noteModalMode"
            @saved="handleNoteSaved"
            @deleted="handleNoteDeleted"
            @share="handleShare"
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

        <!-- Share Dialog -->
        <Dialog v-model:open="shareModal" title="Share Note">
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Share "{{ shareNote?.title }}" with other users
                </p>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">
                        <Users class="mr-1 inline h-3 w-3" />
                        Select Users
                    </label>
                    <ComboBox
                        v-model="shareUserIds"
                        :options="userOptions"
                        placeholder="Search users..."
                        multiple
                    />
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="shareModal = false">Cancel</Button>
                <Button @click="confirmShare" :disabled="shareUserIds.length === 0">
                    Share
                </Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
