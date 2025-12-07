<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Button, Dialog, ComboBox, Input } from '@/Components/ui';
import { NoteGrid, NoteModal, QuickNoteInput, GroupModal } from '@/Components/notes';
import { Users, LayoutGrid, Lock, Loader2, AlertCircle, HelpCircle, ChevronDown, ChevronUp, FolderInput } from 'lucide-vue-next';
import type { NoteData, TagData, GroupData, UserData, NoteFormData } from '@/types/models';

interface DefaultNoteValues {
    is_encrypted?: boolean;
    is_favorited?: boolean;
    is_archived?: boolean;
    is_pinned?: boolean;
}

interface Props {
    fetchUrl: string;
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
    showQuickInput?: boolean;
    defaultNoteValues?: DefaultNoteValues;
    emptyTitle?: string;
    emptyDescription?: string;
    draggable?: boolean;
    filters?: Record<string, unknown>;
}

const props = withDefaults(defineProps<Props>(), {
    showQuickInput: true,
    defaultNoteValues: () => ({}),
    emptyTitle: 'No notes found',
    emptyDescription: 'Create your first note by clicking the input above.',
    draggable: true,
    filters: () => ({}),
});

const noteGridRef = ref<InstanceType<typeof NoteGrid> | null>(null);
const gridCols = ref<3 | 4 | 5>(4);

const toggleGridSize = () => {
    const sizes: (3 | 4 | 5)[] = [3, 4, 5];
    const currentIndex = sizes.indexOf(gridCols.value);
    gridCols.value = sizes[(currentIndex + 1) % sizes.length];
};

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

// Group modal
const groupModalOpen = ref(false);
const selectedGroup = ref<GroupData | null>(null);
const selectedGroupNotes = ref<NoteData[]>([]);

// Unlock modal for encrypted notes
const unlockModal = ref(false);
const noteToUnlock = ref<NoteData | null>(null);
const unlockCode = ref('');
const unlockError = ref('');
const unlockLoading = ref(false);
const decryptedContent = ref<string | null>(null);
const showUnlockHint = ref(false);

// Merge groups modal
const mergeModal = ref(false);
const mergeSourceGroup = ref<GroupData | null>(null);
const mergeTargetGroup = ref<GroupData | null>(null);
const mergeLoading = ref(false);

const userOptions = computed(() => (props.users || []).map((user) => ({
    value: user.id,
    label: user.name,
    description: user.email,
    avatar: user.name.charAt(0).toUpperCase(),
})));

// Expose refresh method
defineExpose({
    refresh: () => noteGridRef.value?.refresh(),
});

// Note actions
const handleView = (note: NoteData) => {
    selectedNote.value = note;
    noteModalMode.value = 'edit';
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
                noteGridRef.value?.refresh();
            },
        });
    }
};

const handleArchive = (note: NoteData) => {
    router.patch(`/notes/${note.id}/archive`, {}, {
        preserveScroll: true,
        onSuccess: () => noteGridRef.value?.refresh(),
    });
};

const handleTogglePin = (note: NoteData) => {
    router.patch(`/notes/${note.id}/pin`, {}, {
        preserveScroll: true,
        onSuccess: () => noteGridRef.value?.refresh(),
    });
};

const handleToggleFavorite = (note: NoteData) => {
    router.patch(`/notes/${note.id}/favorite`, {}, {
        preserveScroll: true,
        onSuccess: () => noteGridRef.value?.refresh(),
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
    noteGridRef.value?.refresh();
};

const handleNoteDeleted = () => {
    noteGridRef.value?.refresh();
};

// Group creation via drag-drop (note onto note)
const handleCreateGroup = async (sourceNoteId: number, targetNoteId: number) => {
    try {
        await axios.post('/api/notes/create-group', {
            source_note_id: sourceNoteId,
            target_note_id: targetNoteId,
        });
        noteGridRef.value?.refresh();
    } catch (error: any) {
        console.error('Failed to create group:', error);
    }
};

// Add note to existing group
const handleAddToGroup = async (noteId: number, groupId: number) => {
    try {
        await axios.post(`/api/notes/${noteId}/add-to-group`, {
            group_id: groupId,
        });
        noteGridRef.value?.refresh();
    } catch (error: any) {
        console.error('Failed to add note to group:', error);
    }
};

// Remove note from group (drag out of group)
const handleRemoveFromGroup = async (noteId: number) => {
    try {
        await axios.post(`/api/notes/${noteId}/remove-from-group`);
        noteGridRef.value?.refresh();
    } catch (error: any) {
        console.error('Failed to remove note from group:', error);
    }
};

const handleViewGroup = (group: GroupData, notes: NoteData[] = []) => {
    selectedGroup.value = group;
    selectedGroupNotes.value = notes;
    groupModalOpen.value = true;
};

const handleGroupSaved = () => {
    noteGridRef.value?.refresh();
};

const handleGroupDeleted = () => {
    noteGridRef.value?.refresh();
};

const handleGroupNoteUpdated = () => {
    noteGridRef.value?.refresh();
};

const handleGroupNoteRemoved = (noteId: number) => {
    selectedGroupNotes.value = selectedGroupNotes.value.filter(n => n.id !== noteId);
    noteGridRef.value?.refresh();
};

const handleGroupNameUpdated = (groupId: number, newName: string) => {
    if (selectedGroup.value && selectedGroup.value.id === groupId) {
        selectedGroup.value = { ...selectedGroup.value, name: newName };
    }
    noteGridRef.value?.refresh();
};

// Handle unlock encrypted note
const handleUnlock = (note: NoteData) => {
    noteToUnlock.value = note;
    unlockCode.value = '';
    unlockError.value = '';
    decryptedContent.value = null;
    showUnlockHint.value = false;
    unlockModal.value = true;
};

const confirmUnlock = async () => {
    if (!noteToUnlock.value || !unlockCode.value.trim()) return;

    unlockLoading.value = true;
    unlockError.value = '';

    try {
        const response = await axios.post(`/api/notes/${noteToUnlock.value.id}/decrypt`, {
            code: unlockCode.value,
        });

        if (response.data.success) {
            decryptedContent.value = response.data.data.content;

            const decryptedNote: NoteData = {
                ...noteToUnlock.value,
                content: response.data.data.content,
                excerpt: response.data.data.content?.substring(0, 200) || '',
            };

            unlockModal.value = false;
            selectedNote.value = decryptedNote;
            noteModalMode.value = 'edit';
            noteModalOpen.value = true;
        }
    } catch (error: any) {
        if (error.response?.status === 429) {
            unlockError.value = 'Too many failed attempts. Please try again later.';
        } else if (error.response?.data?.message) {
            unlockError.value = error.response.data.message;
        } else {
            unlockError.value = 'Failed to decrypt note. Please check your code.';
        }
    } finally {
        unlockLoading.value = false;
    }
};

const closeUnlockModal = () => {
    unlockModal.value = false;
    noteToUnlock.value = null;
    unlockCode.value = '';
    unlockError.value = '';
    decryptedContent.value = null;
    showUnlockHint.value = false;
};

// Handle merge groups
const handleMergeGroups = (sourceGroup: GroupData, targetGroup: GroupData) => {
    mergeSourceGroup.value = sourceGroup;
    mergeTargetGroup.value = targetGroup;
    mergeModal.value = true;
};

const confirmMerge = async () => {
    if (!mergeSourceGroup.value || !mergeTargetGroup.value) return;

    mergeLoading.value = true;
    try {
        await axios.post('/api/groups/merge', {
            source_group_id: mergeSourceGroup.value.id,
            target_group_id: mergeTargetGroup.value.id,
        });
        mergeModal.value = false;
        mergeSourceGroup.value = null;
        mergeTargetGroup.value = null;
        noteGridRef.value?.refresh();
    } catch (error: any) {
        console.error('Failed to merge groups:', error);
    } finally {
        mergeLoading.value = false;
    }
};

const closeMergeModal = () => {
    mergeModal.value = false;
    mergeSourceGroup.value = null;
    mergeTargetGroup.value = null;
};
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0">
        <!-- Quick Note Input (Google Keep style) -->
        <QuickNoteInput
            v-if="showQuickInput"
            class="mb-6 sm:mb-8"
            @click="handleCreateNew"
        />

        <!-- Header with drag hint and grid picker -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <p class="text-sm text-muted-foreground">
                Drag and drop notes onto each other to create groups
            </p>
            <Button
                variant="ghost"
                size="sm"
                @click="toggleGridSize"
                class="self-end sm:self-auto"
            >
                <LayoutGrid class="mr-2 h-4 w-4" />
                {{ gridCols }} cols
            </Button>
        </div>

        <!-- Notes Grid -->
        <NoteGrid
            ref="noteGridRef"
            :fetch-url="fetchUrl"
            :filters="filters"
            :empty-title="emptyTitle"
            :empty-description="emptyDescription"
            :grid-cols="gridCols"
            :draggable="draggable"
            @view="handleView"
            @edit="handleEdit"
            @delete="handleDelete"
            @archive="handleArchive"
            @share="handleShare"
            @toggle-pin="handleTogglePin"
            @toggle-favorite="handleToggleFavorite"
            @unlock="handleUnlock"
            @create-new="handleCreateNew"
            @create-group="handleCreateGroup"
            @add-to-group="handleAddToGroup"
            @remove-from-group="handleRemoveFromGroup"
            @merge-groups="handleMergeGroups"
            @view-group="handleViewGroup"
        />
    </div>

    <!-- Note Modal (View/Edit/Create) -->
    <NoteModal
        v-model:open="noteModalOpen"
        :note="selectedNote"
        :tags="tags"
        :groups="groups"
        :mode="noteModalMode"
        :default-values="defaultNoteValues"
        @saved="handleNoteSaved"
        @deleted="handleNoteDeleted"
        @share="handleShare"
    />

    <!-- Group Modal -->
    <GroupModal
        v-model:open="groupModalOpen"
        :group="selectedGroup"
        :notes="selectedGroupNotes"
        :tags="tags"
        @saved="handleGroupSaved"
        @deleted="handleGroupDeleted"
        @note-updated="handleGroupNoteUpdated"
        @note-removed="handleGroupNoteRemoved"
        @group-name-updated="handleGroupNameUpdated"
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

    <!-- Unlock Encrypted Note Dialog -->
    <Dialog v-model:open="unlockModal" title="Unlock Note" @update:open="!$event && closeUnlockModal()">
        <div class="space-y-4">
            <div class="flex flex-col items-center text-center py-2">
                <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                    <Lock class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="font-semibold text-lg">{{ noteToUnlock?.title }}</h3>
                <p class="text-sm text-muted-foreground">Enter your encryption code to unlock this note</p>
            </div>

            <div v-if="unlockError" class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                <span>{{ unlockError }}</span>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium">Encryption Code</label>
                <Input
                    v-model="unlockCode"
                    type="password"
                    placeholder="Enter your code..."
                    @keyup.enter="confirmUnlock"
                />
            </div>

            <div v-if="noteToUnlock?.encryption_hint" class="border border-border rounded-lg overflow-hidden">
                <button
                    type="button"
                    class="w-full flex items-center justify-between p-3 text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                    @click="showUnlockHint = !showUnlockHint"
                >
                    <span class="flex items-center gap-2">
                        <HelpCircle class="w-4 h-4" />
                        Show hint
                    </span>
                    <component :is="showUnlockHint ? ChevronUp : ChevronDown" class="w-4 h-4" />
                </button>
                <div v-if="showUnlockHint" class="px-3 pb-3 pt-0 text-sm text-muted-foreground bg-muted/30">
                    {{ noteToUnlock.encryption_hint }}
                </div>
            </div>
        </div>
        <template #footer>
            <Button variant="outline" @click="closeUnlockModal">Cancel</Button>
            <Button @click="confirmUnlock" :disabled="!unlockCode.trim() || unlockLoading">
                <Loader2 v-if="unlockLoading" class="mr-2 h-4 w-4 animate-spin" />
                <Lock v-else class="mr-2 h-4 w-4" />
                {{ unlockLoading ? 'Unlocking...' : 'Unlock' }}
            </Button>
        </template>
    </Dialog>

    <!-- Merge Groups Confirmation Dialog -->
    <Dialog v-model:open="mergeModal" title="Merge Groups" @update:open="!$event && closeMergeModal()">
        <div class="space-y-4">
            <div class="flex flex-col items-center text-center py-2">
                <div class="w-14 h-14 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-3">
                    <FolderInput class="w-7 h-7 text-orange-600 dark:text-orange-400" />
                </div>
                <h3 class="font-semibold text-lg">Merge Groups</h3>
                <p class="text-sm text-muted-foreground mt-1">
                    Are you sure you want to merge these groups?
                </p>
            </div>

            <div class="bg-muted/50 rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary text-sm font-medium">
                        A
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">{{ mergeSourceGroup?.name }}</p>
                        <p class="text-xs text-muted-foreground">Will be merged and removed</p>
                    </div>
                </div>
                <div class="flex justify-center">
                    <ChevronDown class="w-5 h-5 text-muted-foreground" />
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center text-green-600 text-sm font-medium">
                        B
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">{{ mergeTargetGroup?.name }}</p>
                        <p class="text-xs text-muted-foreground">Will contain all notes</p>
                    </div>
                </div>
            </div>

            <p class="text-sm text-muted-foreground text-center">
                All notes from "{{ mergeSourceGroup?.name }}" will be moved to "{{ mergeTargetGroup?.name }}",
                and "{{ mergeSourceGroup?.name }}" will be deleted.
            </p>
        </div>
        <template #footer>
            <Button variant="outline" @click="closeMergeModal">Cancel</Button>
            <Button @click="confirmMerge" :disabled="mergeLoading">
                <Loader2 v-if="mergeLoading" class="mr-2 h-4 w-4 animate-spin" />
                <FolderInput v-else class="mr-2 h-4 w-4" />
                {{ mergeLoading ? 'Merging...' : 'Merge Groups' }}
            </Button>
        </template>
    </Dialog>
</template>
