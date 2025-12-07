<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Dialog, Button, Input, Textarea, Label, Badge, ComboBox } from '@/Components/ui';
import { cn } from '@/lib/utils';
import type { NoteData, TagData, GroupData } from '@/types/models';
import {
    X,
    Save,
    Trash2,
    Pin,
    Star,
    Lock,
    Palette,
    Tag,
    Loader2,
    FolderOpen,
    Edit3,
    Check,
    ChevronRight,
    ChevronDown,
    ChevronUp,
    Unlink,
    AlertCircle,
    HelpCircle,
} from 'lucide-vue-next';

interface Props {
    open: boolean;
    group: GroupData | null;
    notes: NoteData[];
    tags: TagData[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
    deleted: [];
    noteUpdated: [];
    noteRemoved: [noteId: number];
    groupNameUpdated: [groupId: number, newName: string];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Group editing state
const isEditingGroupName = ref(false);
const groupName = ref('');
const groupNameInput = ref<{ focus: () => void; select: () => void } | null>(null);

// Selected note for editing
const selectedNote = ref<NoteData | null>(null);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const showOptions = ref(false);

// Unlock state for encrypted notes
const unlockCode = ref('');
const unlockError = ref('');
const unlockLoading = ref(false);
const showHint = ref(false);
const isUnlocked = ref(false);

const tagOptions = computed(() =>
    props.tags.map((tag) => ({
        value: tag.id,
        label: tag.name,
        color: tag.color,
    }))
);

// Note form
const form = ref({
    title: '',
    content: '',
    tag_ids: [] as number[],
    color: '',
    is_pinned: false,
});

const colorOptions = [
    { value: '', label: 'None', class: 'bg-muted border-muted-foreground/20' },
    { value: '#fef3c7', label: 'Yellow', class: 'bg-yellow-100 border-yellow-300' },
    { value: '#dcfce7', label: 'Green', class: 'bg-green-100 border-green-300' },
    { value: '#dbeafe', label: 'Blue', class: 'bg-blue-100 border-blue-300' },
    { value: '#fce7f3', label: 'Pink', class: 'bg-pink-100 border-pink-300' },
    { value: '#f3e8ff', label: 'Purple', class: 'bg-purple-100 border-purple-300' },
    { value: '#fed7aa', label: 'Orange', class: 'bg-orange-100 border-orange-300' },
    { value: '#e5e7eb', label: 'Gray', class: 'bg-gray-200 border-gray-300' },
];

const canSave = computed(() => form.value.title.trim().length > 0);

// Convert hex to RGB for light background
const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

const getNoteCardStyle = (note: NoteData) => {
    if (!note.color) return {};
    const rgb = hexToRgb(note.color);
    if (!rgb) return {};
    return {
        backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.15)`,
        borderColor: note.color,
    };
};

const editorBackground = computed(() => {
    if (form.value.color) {
        const rgb = hexToRgb(form.value.color);
        if (rgb) {
            return { backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.12)` };
        }
    }
    return {};
});

// Initialize when modal opens
watch(
    () => props.open,
    (open) => {
        if (open && props.group) {
            groupName.value = props.group.name;
            // Select first note by default
            if (props.notes.length > 0) {
                selectNote(props.notes[0]);
            } else {
                selectedNote.value = null;
                resetForm();
            }
        }
    }
);

// Watch for notes changes
watch(
    () => props.notes,
    (newNotes) => {
        if (selectedNote.value) {
            // Update selected note if it still exists
            const updated = newNotes.find(n => n.id === selectedNote.value?.id);
            if (updated) {
                selectedNote.value = updated;
                populateForm(updated);
            } else if (newNotes.length > 0) {
                selectNote(newNotes[0]);
            } else {
                selectedNote.value = null;
                resetForm();
            }
        }
    },
    { deep: true }
);

function resetForm() {
    form.value = {
        title: '',
        content: '',
        tag_ids: [],
        color: '',
        is_pinned: false,
    };
    errors.value = {};
}

function populateForm(note: NoteData) {
    form.value = {
        title: note.title || '',
        content: note.content || note.excerpt || '',
        tag_ids: note.tags?.map(t => t.id) || [],
        color: note.color || '',
        is_pinned: note.is_pinned || false,
    };
    errors.value = {};
}

function selectNote(note: NoteData) {
    selectedNote.value = note;
    populateForm(note);
    showOptions.value = false;
    // Reset unlock state
    unlockCode.value = '';
    unlockError.value = '';
    showHint.value = false;
    isUnlocked.value = false;
}

async function unlockNote() {
    if (!selectedNote.value || !unlockCode.value.trim()) return;

    unlockLoading.value = true;
    unlockError.value = '';

    try {
        const response = await axios.post(`/api/notes/${selectedNote.value.id}/decrypt`, {
            code: unlockCode.value,
        });

        if (response.data.success) {
            // Update the form with decrypted content
            form.value.content = response.data.data.content;
            isUnlocked.value = true;
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
}

async function updateTags(tagIds: string | number | (string | number)[]) {
    const ids = Array.isArray(tagIds) ? tagIds.map(Number) : [Number(tagIds)];
    form.value.tag_ids = ids;
    if (selectedNote.value) {
        try {
            await axios.patch(`/api/notes/${selectedNote.value.id}/tags`, { tag_ids: ids });
            emit('noteUpdated');
        } catch (err) {
            console.error('Failed to update tags:', err);
        }
    }
}

async function updateColor(color: string) {
    form.value.color = color;
    if (selectedNote.value) {
        try {
            await axios.patch(`/api/notes/${selectedNote.value.id}/color`, { color });
            emit('noteUpdated');
        } catch (err) {
            console.error('Failed to update color:', err);
        }
    }
}

function handleClose() {
    isOpen.value = false;
    isEditingGroupName.value = false;
    selectedNote.value = null;
    resetForm();
}

// Group name editing
function startEditingGroupName() {
    isEditingGroupName.value = true;
    nextTick(() => {
        groupNameInput.value?.focus();
        groupNameInput.value?.select();
    });
}

async function saveGroupName() {
    if (!props.group || !groupName.value.trim()) return;

    const newName = groupName.value.trim();
    try {
        await axios.put(`/api/groups/${props.group.id}`, {
            name: newName,
        });
        isEditingGroupName.value = false;
        emit('groupNameUpdated', props.group.id, newName);
        emit('saved');
    } catch (err: any) {
        console.error('Failed to update group name:', err);
    }
}

function cancelEditingGroupName() {
    if (props.group) {
        groupName.value = props.group.name;
    }
    isEditingGroupName.value = false;
}

// Note actions
async function handleSaveNote() {
    if (!selectedNote.value || !canSave.value || submitting.value) return;

    submitting.value = true;
    errors.value = {};

    router.put(`/notes/${selectedNote.value.id}`, form.value, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('noteUpdated');
        },
        onError: (errs) => {
            errors.value = errs as Record<string, string>;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

async function handleRemoveFromGroup() {
    if (!selectedNote.value || !props.group || submitting.value) return;

    submitting.value = true;
    try {
        await axios.post(`/api/notes/${selectedNote.value.id}/remove-from-group`);
        emit('noteRemoved', selectedNote.value.id);

        // Select next note or close if no notes left
        const remainingNotes = props.notes.filter(n => n.id !== selectedNote.value?.id);
        if (remainingNotes.length > 0) {
            selectNote(remainingNotes[0]);
        } else {
            handleClose();
        }
    } catch (err: any) {
        console.error('Failed to remove note from group:', err);
    } finally {
        submitting.value = false;
    }
}

function handleTogglePin() {
    form.value.is_pinned = !form.value.is_pinned;
}

async function handleDeleteGroup() {
    if (!props.group || submitting.value) return;

    if (!confirm('Delete this group? Notes will be ungrouped but not deleted.')) return;

    submitting.value = true;
    try {
        await axios.delete(`/api/groups/${props.group.id}`);
        emit('deleted');
        handleClose();
    } catch (err: any) {
        console.error('Failed to delete group:', err);
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="isOpen = $event"
        size="5xl"
        hide-close
    >
        <template #default>
            <div class="flex flex-col h-[90vh] sm:h-[80vh] lg:h-[75vh] -m-6">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-border bg-muted/30">
                    <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                        <div class="hidden sm:flex items-center justify-center w-10 h-10 rounded-xl bg-primary/10 text-primary flex-shrink-0">
                            <FolderOpen class="w-5 h-5" />
                        </div>

                        <!-- Editable Group Name -->
                        <div class="flex-1 min-w-0">
                            <div v-if="isEditingGroupName" class="flex items-center">
                                <div class="relative flex items-center w-full max-w-xs">
                                    <Input
                                        ref="groupNameInput"
                                        v-model="groupName"
                                        class="text-base sm:text-lg font-semibold h-8 pr-16"
                                        @keyup.enter="saveGroupName"
                                        @keyup.escape="cancelEditingGroupName"
                                    />
                                    <div class="absolute right-1 flex items-center gap-0.5">
                                        <button
                                            type="button"
                                            class="p-1 rounded hover:bg-green-100 text-green-600"
                                            @click="saveGroupName"
                                        >
                                            <Check class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 rounded hover:bg-muted text-muted-foreground"
                                            @click="cancelEditingGroupName"
                                        >
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex items-center gap-2 group cursor-pointer" @click="startEditingGroupName">
                                <h2 class="text-lg sm:text-xl font-semibold text-foreground truncate">{{ groupName }}</h2>
                                <Edit3 class="w-4 h-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" />
                            </div>
                            <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                                {{ notes.length }} {{ notes.length === 1 ? 'note' : 'notes' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                        <Button variant="ghost" size="icon" class="text-destructive h-8 w-8 sm:h-9 sm:w-9" @click="handleDeleteGroup" title="Delete group">
                            <Trash2 class="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="icon" class="h-8 w-8 sm:h-9 sm:w-9" @click="handleClose">
                            <X class="w-5 h-5" />
                        </Button>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
                    <!-- Notes List -->
                    <div class="w-full lg:w-72 xl:w-80 border-b lg:border-b-0 lg:border-r border-border flex flex-col bg-muted/20 max-h-[30vh] lg:max-h-none">
                        <div class="p-2 sm:p-3 border-b border-border">
                            <h3 class="text-xs sm:text-sm font-medium text-muted-foreground uppercase tracking-wider">Notes in Group</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 sm:p-3 space-y-2">
                            <div
                                v-for="note in notes"
                                :key="note.id"
                                :class="cn(
                                    'p-2 sm:p-3 rounded-lg border-2 cursor-pointer transition-all',
                                    'hover:shadow-md',
                                    selectedNote?.id === note.id
                                        ? 'ring-2 ring-primary ring-offset-1'
                                        : 'hover:border-muted-foreground/30'
                                )"
                                :style="getNoteCardStyle(note)"
                                @click="selectNote(note)"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <Pin v-if="note.is_pinned" class="w-3 h-3 text-primary flex-shrink-0" />
                                            <Lock v-if="note.is_encrypted" class="w-3 h-3 text-muted-foreground flex-shrink-0" />
                                            <h4 class="font-medium text-sm truncate">{{ note.title }}</h4>
                                        </div>
                                        <p class="text-xs text-muted-foreground line-clamp-2 mt-1 hidden sm:block">
                                            {{ note.is_encrypted ? 'Encrypted content' : (note.excerpt || 'No content') }}
                                        </p>
                                    </div>
                                    <ChevronRight
                                        v-if="selectedNote?.id === note.id"
                                        class="w-4 h-4 text-primary flex-shrink-0 mt-0.5"
                                    />
                                </div>

                                <!-- Tags -->
                                <div v-if="note.tags && note.tags.length > 0" class="hidden sm:flex flex-wrap gap-1 mt-2">
                                    <Badge
                                        v-for="tag in note.tags.slice(0, 3)"
                                        :key="tag.id"
                                        :color="tag.color"
                                        size="sm"
                                        class="text-[10px]"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                    <span v-if="note.tags.length > 3" class="text-[10px] text-muted-foreground">
                                        +{{ note.tags.length - 3 }}
                                    </span>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-if="notes.length === 0" class="text-center py-4 sm:py-8 text-muted-foreground">
                                <FolderOpen class="w-8 h-8 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 opacity-50" />
                                <p class="text-sm">No notes in this group</p>
                            </div>
                        </div>
                    </div>

                    <!-- Note Editor -->
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <div v-if="selectedNote" class="flex flex-col h-full">
                            <!-- Unlock Prompt for Encrypted Notes -->
                            <div v-if="selectedNote.is_encrypted && !isUnlocked" class="flex-1 flex items-center justify-center p-6">
                                <div class="w-full max-w-sm space-y-6">
                                    <!-- Lock icon and title -->
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4">
                                            <Lock class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <h3 class="font-semibold text-xl mb-1">{{ selectedNote.title }}</h3>
                                        <p class="text-sm text-muted-foreground">Enter your encryption code to unlock this note</p>
                                    </div>

                                    <!-- Error message -->
                                    <div v-if="unlockError" class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                                        <AlertCircle class="w-4 h-4 flex-shrink-0" />
                                        <span>{{ unlockError }}</span>
                                    </div>

                                    <!-- Code input -->
                                    <div>
                                        <Input
                                            v-model="unlockCode"
                                            type="password"
                                            placeholder="Enter encryption code..."
                                            @keyup.enter="unlockNote"
                                        />
                                    </div>

                                    <!-- Hint accordion (hidden by default) -->
                                    <div v-if="selectedNote.encryption_hint" class="border border-border rounded-lg overflow-hidden">
                                        <button
                                            type="button"
                                            class="w-full flex items-center justify-between p-3 text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                                            @click="showHint = !showHint"
                                        >
                                            <span class="flex items-center gap-2">
                                                <HelpCircle class="w-4 h-4" />
                                                Show hint
                                            </span>
                                            <component :is="showHint ? ChevronUp : ChevronDown" class="w-4 h-4" />
                                        </button>
                                        <div v-if="showHint" class="px-3 pb-3 pt-0 text-sm text-muted-foreground bg-muted/30">
                                            {{ selectedNote.encryption_hint }}
                                        </div>
                                    </div>

                                    <!-- Unlock button -->
                                    <Button class="w-full" @click="unlockNote" :disabled="!unlockCode.trim() || unlockLoading">
                                        <Loader2 v-if="unlockLoading" class="mr-2 h-4 w-4 animate-spin" />
                                        <Lock v-else class="mr-2 h-4 w-4" />
                                        {{ unlockLoading ? 'Unlocking...' : 'Unlock Note' }}
                                    </Button>

                                    <!-- Remove from group option -->
                                    <div class="pt-4 border-t border-border">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full text-orange-600 border-orange-200 hover:bg-orange-50"
                                            @click="handleRemoveFromGroup"
                                            :disabled="submitting"
                                        >
                                            <Unlink class="w-4 h-4 mr-2" />
                                            Remove from Group
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Editor Content (for unlocked or non-encrypted notes) -->
                            <template v-else>
                                <div
                                    class="flex-1 overflow-y-auto p-4 sm:p-6 transition-colors"
                                    :style="editorBackground"
                                >
                                    <!-- Title -->
                                    <div class="mb-3 sm:mb-4">
                                        <Input
                                            v-model="form.title"
                                            placeholder="Note title"
                                            :error="errors.title"
                                            class="text-xl sm:text-2xl font-semibold border-0 shadow-none focus:ring-0 px-0 bg-transparent h-auto"
                                        />
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <Textarea
                                            v-model="form.content"
                                            placeholder="Write your note..."
                                            :rows="8"
                                            :error="errors.content"
                                            class="resize-none border-0 shadow-none focus:ring-0 px-0 bg-transparent text-base leading-relaxed"
                                        />
                                    </div>

                                <!-- Tags Display -->
                                <div v-if="form.tag_ids.length > 0" class="flex flex-wrap gap-1.5 mt-4">
                                    <Badge
                                        v-for="tagId in form.tag_ids"
                                        :key="tagId"
                                        :color="tags.find(t => t.id === tagId)?.color"
                                        size="sm"
                                    >
                                        {{ tags.find(t => t.id === tagId)?.name }}
                                    </Badge>
                                </div>

                                <!-- Options Panel (collapsible) -->
                                <div class="border-t border-border/50 pt-4 mt-4">
                                    <button
                                        type="button"
                                        class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-4"
                                        @click="showOptions = !showOptions"
                                    >
                                        <component :is="showOptions ? ChevronUp : ChevronDown" class="w-4 h-4" />
                                        {{ showOptions ? 'Hide options' : 'More options' }}
                                    </button>

                                    <div v-if="showOptions" class="space-y-4 animate-in slide-in-from-top-2 duration-200">
                                        <!-- Color Selection -->
                                        <div>
                                            <Label class="text-xs text-muted-foreground mb-2 flex items-center gap-1.5">
                                                <Palette class="h-3.5 w-3.5" />
                                                Background Color
                                            </Label>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="color in colorOptions"
                                                    :key="color.value"
                                                    type="button"
                                                    :class="cn(
                                                        'h-7 w-7 sm:h-8 sm:w-8 rounded-full border-2 transition-all',
                                                        form.color === color.value
                                                            ? 'ring-2 ring-primary ring-offset-2'
                                                            : 'hover:scale-110',
                                                        color.class
                                                    )"
                                                    :style="color.value ? { backgroundColor: color.value, borderColor: color.value } : {}"
                                                    :title="color.label"
                                                    @click="updateColor(color.value)"
                                                />
                                            </div>
                                        </div>

                                        <!-- Tags Selection -->
                                        <div>
                                            <Label class="text-xs text-muted-foreground mb-2 flex items-center gap-1.5">
                                                <Tag class="h-3.5 w-3.5" />
                                                Tags
                                            </Label>
                                            <ComboBox
                                                :model-value="form.tag_ids"
                                                :options="tagOptions"
                                                placeholder="Select tags..."
                                                multiple
                                                @update:model-value="updateTags"
                                            />
                                        </div>

                                        <!-- Pin Toggle -->
                                        <div class="flex items-center justify-between py-2">
                                            <div class="flex items-center gap-2">
                                                <Pin class="h-4 w-4 text-muted-foreground" />
                                                <Label class="text-sm font-normal">Pin this note</Label>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                :class="form.is_pinned ? 'text-primary' : 'text-muted-foreground'"
                                                @click="handleTogglePin"
                                            >
                                                {{ form.is_pinned ? 'Pinned' : 'Pin' }}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editor Footer -->
                                <div class="border-t border-border px-4 sm:px-6 py-3 sm:py-4 bg-background">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-orange-600 border-orange-200 hover:bg-orange-50"
                                            @click="handleRemoveFromGroup"
                                            :disabled="submitting"
                                        >
                                            <Unlink class="w-4 h-4 sm:mr-1.5" />
                                            <span class="hidden sm:inline">Remove from Group</span>
                                            <span class="sm:hidden">Remove</span>
                                        </Button>

                                        <Button
                                            size="sm"
                                            @click="handleSaveNote"
                                            :disabled="!canSave || submitting"
                                        >
                                            <Loader2 v-if="submitting" class="w-4 h-4 sm:mr-1.5 animate-spin" />
                                            <Save v-else class="w-4 h-4 sm:mr-1.5" />
                                            <span class="hidden sm:inline">Save Changes</span>
                                            <span class="sm:hidden">Save</span>
                                        </Button>
                                    </div>

                                    <!-- Error display -->
                                    <div v-if="errors.general" class="mt-3 p-3 rounded-md bg-destructive/10 text-destructive text-sm">
                                        {{ errors.general }}
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- No note selected state -->
                        <div v-else class="flex-1 flex items-center justify-center text-muted-foreground">
                            <div class="text-center">
                                <Edit3 class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 opacity-50" />
                                <p class="text-sm">Select a note to edit</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>
