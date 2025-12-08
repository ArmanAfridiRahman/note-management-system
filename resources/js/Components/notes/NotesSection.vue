<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Button, Dialog, ComboBox, Input, Label, Textarea, Select, DateTimePicker } from '@/Components/ui';
import { NoteGrid, NoteModal, QuickNoteInput, GroupModal } from '@/Components/notes';
import { Users, LayoutGrid, Lock, Loader2, AlertCircle, HelpCircle, ChevronDown, ChevronUp, FolderInput, X, Eye, Pencil, Clock, Search } from 'lucide-vue-next';
import { useToast } from '@/Composables/useToast';
import type { NoteData, TagData, GroupData, UserData, NoteFormData } from '@/types/models';

const toast = useToast();

interface ExistingShare {
    id: number;
    user: UserData;
    permission: 'view' | 'edit';
    expires_at: string | null;
    message: string | null;
    created_at: string;
}

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
    showSearch?: boolean;
    defaultNoteValues?: DefaultNoteValues;
    emptyTitle?: string;
    emptyDescription?: string;
    draggable?: boolean;
    filters?: Record<string, unknown>;
}

const props = withDefaults(defineProps<Props>(), {
    showQuickInput: true,
    showSearch: true,
    defaultNoteValues: () => ({}),
    emptyTitle: 'No notes found',
    emptyDescription: 'Create your first note by clicking the input above.',
    draggable: true,
    filters: () => ({}),
});

// Search functionality
const searchQuery = ref('');
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

const combinedFilters = computed(() => {
    const filters = { ...props.filters };
    if (searchQuery.value.trim()) {
        filters.search = searchQuery.value.trim();
    }
    return filters;
});

const handleSearchInput = () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    searchTimeout.value = setTimeout(() => {
        // The filter change will trigger NoteGrid to refetch
    }, 300);
};

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
const sharePermission = ref<'view' | 'edit'>('view');
const shareExpiresAt = ref<Date | null>(null);
const shareMessage = ref('');
const shareLoading = ref(false);
const shareError = ref('');
const existingShares = ref<ExistingShare[]>([]);
const existingSharesLoading = ref(false);
const revokeLoading = ref<number | null>(null);

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
const handleView = async (note: NoteData) => {
    selectedNote.value = note;
    noteModalMode.value = 'edit';
    noteModalOpen.value = true;

    // Increment open count in background
    try {
        await axios.post(`/api/notes/${note.id}/open`);
    } catch (err) {
        // Silently fail - not critical
        console.error('Failed to increment open count:', err);
    }
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

const handleShare = async (note: NoteData | { id?: number; title: string }) => {
    if (note.id) {
        shareNote.value = note as NoteData;
        shareUserIds.value = [];
        sharePermission.value = 'view';
        shareExpiresAt.value = null;
        shareMessage.value = '';
        shareError.value = '';
        existingShares.value = [];
        shareModal.value = true;

        // Fetch existing shares for this note
        existingSharesLoading.value = true;
        try {
            const response = await axios.get(`/api/notes/${note.id}/shares`);
            if (response.data.success) {
                existingShares.value = response.data.data;
                // Pre-select users who already have access
                shareUserIds.value = existingShares.value.map((share) => share.user.id);

                // Pre-fill permission, expiration, and message from the first share
                if (existingShares.value.length > 0) {
                    const firstShare = existingShares.value[0];
                    sharePermission.value = firstShare.permission;
                    if (firstShare.expires_at) {
                        shareExpiresAt.value = new Date(firstShare.expires_at);
                    }
                    if (firstShare.message) {
                        shareMessage.value = firstShare.message;
                    }
                }
            }
        } catch (error) {
            console.error('Failed to fetch existing shares:', error);
        } finally {
            existingSharesLoading.value = false;
        }
    }
};

const confirmShare = async () => {
    if (!shareNote.value || shareUserIds.value.length === 0) return;

    shareLoading.value = true;
    shareError.value = '';

    try {
        const payload: Record<string, any> = {
            note_id: shareNote.value.id,
            user_ids: shareUserIds.value,
            permission: sharePermission.value,
        };

        if (shareExpiresAt.value) {
            // Format date as ISO string for the API
            payload.expires_at = shareExpiresAt.value instanceof Date
                ? shareExpiresAt.value.toISOString()
                : shareExpiresAt.value;
        }

        if (shareMessage.value.trim()) {
            payload.message = shareMessage.value.trim();
        }

        const response = await axios.post('/api/shares', payload);

        if (response.data.success) {
            shareModal.value = false;
            shareNote.value = null;
            shareUserIds.value = [];
            sharePermission.value = 'view';
            shareExpiresAt.value = null;
            shareMessage.value = '';
            existingShares.value = [];
            noteGridRef.value?.refresh();
            toast.success(response.data.message || 'Note shared successfully.');
        }
    } catch (error: any) {
        shareError.value = error.response?.data?.message || 'Failed to share note. Please try again.';
        toast.error(shareError.value);
    } finally {
        shareLoading.value = false;
    }
};

const revokeShare = async (shareId: number) => {
    revokeLoading.value = shareId;
    try {
        // Find the share before removing it
        const share = existingShares.value.find((s) => s.id === shareId);
        const response = await axios.delete(`/api/shares/${shareId}`);
        // Remove from existing shares
        existingShares.value = existingShares.value.filter((s) => s.id !== shareId);
        // Also remove from selected user ids
        if (share) {
            shareUserIds.value = shareUserIds.value.filter((id) => id !== share.user.id);
        }
        noteGridRef.value?.refresh();
        toast.success(response.data?.message || 'Share revoked successfully.');
    } catch (error: any) {
        shareError.value = error.response?.data?.message || 'Failed to revoke share.';
        toast.error(shareError.value);
    } finally {
        revokeLoading.value = null;
    }
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
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
                excerpt: response.data.data.excerpt || response.data.data.content?.substring(0, 200) || '',
            };

            unlockModal.value = false;
            selectedNote.value = decryptedNote;
            noteModalMode.value = 'edit';
            noteModalOpen.value = true;
            toast.success('Note unlocked successfully.');

            // Increment open count in background
            try {
                await axios.post(`/api/notes/${noteToUnlock.value.id}/open`);
            } catch (err) {
                // Silently fail - not critical
            }
        }
    } catch (error: any) {
        if (error.response?.status === 429) {
            unlockError.value = 'Too many failed attempts. Please try again later.';
        } else if (error.response?.data?.message) {
            unlockError.value = error.response.data.message;
        } else {
            unlockError.value = 'Failed to decrypt note. Please check your code.';
        }
        toast.error(unlockError.value);
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
        const response = await axios.post('/api/groups/merge', {
            source_group_id: mergeSourceGroup.value.id,
            target_group_id: mergeTargetGroup.value.id,
        });
        mergeModal.value = false;
        mergeSourceGroup.value = null;
        mergeTargetGroup.value = null;
        noteGridRef.value?.refresh();
        toast.success(response.data?.message || 'Groups merged successfully.');
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Failed to merge groups.';
        toast.error(errorMessage);
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
        <!-- Search Input -->
        <div v-if="showSearch" class="mb-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search notes by title..."
                    class="w-full h-10 pl-10 pr-10 rounded-lg border border-input bg-background text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    @input="handleSearchInput"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    @click="searchQuery = ''"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

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
            :filters="combinedFilters"
            :empty-title="searchQuery ? 'No matching notes' : emptyTitle"
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
    <Dialog v-model:open="shareModal" title="Share Note" size="md">
        <div class="space-y-5">
            <p class="text-sm text-muted-foreground">
                Share "{{ shareNote?.title }}" with other users
            </p>

            <!-- Error Message -->
            <div v-if="shareError" class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                <span>{{ shareError }}</span>
            </div>

            <!-- Existing Shares List -->
            <div v-if="existingSharesLoading" class="flex items-center justify-center py-4">
                <Loader2 class="w-5 h-5 animate-spin text-muted-foreground" />
                <span class="ml-2 text-sm text-muted-foreground">Loading existing shares...</span>
            </div>
            <div v-else-if="existingShares.length > 0">
                <Label class="mb-1.5 block text-sm font-medium">
                    <Users class="mr-1 inline h-3.5 w-3.5" />
                    Current Access ({{ existingShares.length }})
                </Label>
                <div class="border border-border rounded-lg max-h-40 overflow-y-auto">
                    <div
                        v-for="share in existingShares"
                        :key="share.id"
                        class="flex items-center justify-between p-3 border-b border-border last:border-b-0 hover:bg-muted/30 transition-colors"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-medium text-primary">
                                    {{ share.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ share.user?.name }}</p>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Eye v-if="share.permission === 'view'" class="w-3 h-3" />
                                        <Pencil v-else class="w-3 h-3" />
                                        {{ share.permission === 'view' ? 'View' : 'Edit' }}
                                    </span>
                                    <span v-if="share.expires_at" class="flex items-center gap-1">
                                        <Clock class="w-3 h-3" />
                                        {{ formatDate(share.expires_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="revokeShare(share.id)"
                            :disabled="revokeLoading === share.id"
                            class="text-destructive hover:text-destructive hover:bg-destructive/10 flex-shrink-0"
                        >
                            <Loader2 v-if="revokeLoading === share.id" class="w-4 h-4 animate-spin" />
                            <X v-else class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Select Users -->
            <div>
                <Label class="mb-1.5 block text-sm font-medium">
                    <Users class="mr-1 inline h-3.5 w-3.5" />
                    {{ existingShares.length > 0 ? 'Add More Users' : 'Select Users' }}
                </Label>
                <ComboBox
                    v-model="shareUserIds"
                    :options="userOptions"
                    placeholder="Search users..."
                    multiple
                />
            </div>

            <!-- Permission Selection -->
            <div>
                <Label class="mb-1.5 block text-sm font-medium">Permission</Label>
                <div class="flex gap-3">
                    <label
                        :class="[
                            'flex-1 flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-colors',
                            sharePermission === 'view'
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-muted-foreground/50'
                        ]"
                    >
                        <input
                            type="radio"
                            v-model="sharePermission"
                            value="view"
                            class="sr-only"
                        />
                        <div class="flex-1">
                            <p class="font-medium text-sm">View only</p>
                            <p class="text-xs text-muted-foreground">Can read but not edit</p>
                        </div>
                    </label>
                    <label
                        :class="[
                            'flex-1 flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-colors',
                            sharePermission === 'edit'
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-muted-foreground/50'
                        ]"
                    >
                        <input
                            type="radio"
                            v-model="sharePermission"
                            value="edit"
                            class="sr-only"
                        />
                        <div class="flex-1">
                            <p class="font-medium text-sm">Can edit</p>
                            <p class="text-xs text-muted-foreground">Can read and modify</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Expiration Date (Optional) -->
            <div>
                <Label class="mb-1.5 block text-sm font-medium">
                    Expiration Date
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <DateTimePicker
                    v-model="shareExpiresAt"
                    placeholder="Select expiration date and time"
                    :min-date="new Date()"
                    :clearable="true"
                />
                <p class="mt-1 text-xs text-muted-foreground">
                    Leave empty for permanent access
                </p>
            </div>

            <!-- Message (Optional) -->
            <div>
                <Label class="mb-1.5 block text-sm font-medium">
                    Message
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <Textarea
                    v-model="shareMessage"
                    placeholder="Add a message for the recipients..."
                    :rows="2"
                />
            </div>
        </div>
        <template #footer>
            <Button variant="outline" @click="shareModal = false" :disabled="shareLoading">
                Cancel
            </Button>
            <Button @click="confirmShare" :disabled="shareUserIds.length === 0 || shareLoading">
                <Loader2 v-if="shareLoading" class="mr-2 h-4 w-4 animate-spin" />
                {{ shareLoading ? 'Saving...' : (existingShares.length > 0 ? 'Update Sharing' : 'Share') }}
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
