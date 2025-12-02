<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Dialog, Button, Input, Textarea, Label, Badge, Toggle, ComboBox } from '@/Components/ui';
import { cn } from '@/lib/utils';
import { useAutoSave } from '@/Composables/useAutoSave';
import { appConfig } from '@/config/app';
import type { NoteData, TagData, GroupData } from '@/types/models';
import {
    Save,
    Trash2,
    Pin,
    Star,
    Lock,
    Archive,
    Share2,
    Tag,
    Folder,
    Palette,
    MoreHorizontal,
    Loader2,
    Cloud,
    CloudOff,
} from 'lucide-vue-next';

// Extended NoteData for modal (id can be undefined for new notes)
interface ModalNoteData extends Partial<NoteData> {
    title: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    tags: TagData[];
}

interface Props {
    open: boolean;
    note?: NoteData | null;
    tags: TagData[];
    groups: GroupData[];
    mode?: 'view' | 'edit' | 'create';
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
    deleted: [];
    share: [note: ModalNoteData];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const currentMode = ref(props.mode);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const showOptions = ref(false);
const titleInput = ref<HTMLInputElement | null>(null);
const currentNoteId = ref<number | undefined>(undefined);

const form = ref({
    title: '',
    content: '',
    group_id: '',
    tag_ids: [] as number[],
    is_encrypted: false,
    encryption_password: '',
    encryption_hint: '',
    color: '',
    is_pinned: false,
});

const autoSaveEnabled = ref(false);

// Auto-save functionality
const {
    isDirty,
    isSaving,
    lastSaved,
    error: autoSaveError,
    save: autoSave,
    reset: resetAutoSave,
    init: initAutoSave,
    clearLocal,
} = useAutoSave({
    data: form,
    storageKey: appConfig.storage.draftNote,
    enabled: autoSaveEnabled,
    onSave: async (data) => {
        if (!currentNoteId.value) {
            // Create new note
            const response = await axios.post('/notes', data);
            currentNoteId.value = response.data.id;
            emit('saved');
        } else {
            // Update existing note
            await axios.put(`/notes/${currentNoteId.value}`, data);
            emit('saved');
        }
    },
});

const colorOptions = [
    { value: '', label: 'None', bg: 'bg-muted' },
    { value: '#fef3c7', label: 'Yellow', bg: 'bg-yellow-100' },
    { value: '#dcfce7', label: 'Green', bg: 'bg-green-100' },
    { value: '#dbeafe', label: 'Blue', bg: 'bg-blue-100' },
    { value: '#fce7f3', label: 'Pink', bg: 'bg-pink-100' },
    { value: '#f3e8ff', label: 'Purple', bg: 'bg-purple-100' },
    { value: '#fed7aa', label: 'Orange', bg: 'bg-orange-100' },
    { value: '#e5e7eb', label: 'Gray', bg: 'bg-gray-200' },
];

const tagOptions = computed(() =>
    props.tags.map((tag) => ({
        value: tag.id,
        label: tag.name,
        color: tag.color,
    }))
);

const groupOptions = computed(() => [
    { value: '', label: 'No Group' },
    ...props.groups.map((group) => ({
        value: group.id.toString(),
        label: group.name,
        color: group.color,
    })),
]);

const isEditing = computed(() => currentMode.value === 'edit' || currentMode.value === 'create');
const isNewNote = computed(() => currentMode.value === 'create' && !currentNoteId.value);
const canSave = computed(() => form.value.title.trim().length > 0);

const modalBackground = computed(() => {
    if (form.value.color) {
        return { backgroundColor: form.value.color };
    }
    return {};
});

const saveStatusText = computed(() => {
    if (isSaving.value) return 'Saving...';
    if (autoSaveError.value) return 'Save failed';
    if (lastSaved.value) {
        const seconds = Math.floor((Date.now() - lastSaved.value.getTime()) / 1000);
        if (seconds < 5) return 'Saved';
        if (seconds < 60) return `Saved ${seconds}s ago`;
        return `Saved ${Math.floor(seconds / 60)}m ago`;
    }
    if (isDirty.value) return 'Unsaved changes';
    return '';
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            currentMode.value = props.mode;
            currentNoteId.value = props.note?.id;
            resetForm();

            if (props.note && props.mode !== 'create') {
                populateForm(props.note);
            }

            // Enable auto-save when editing
            autoSaveEnabled.value = props.mode !== 'view';

            // Initialize auto-save (load drafts for new notes)
            if (props.mode === 'create') {
                initAutoSave(true);
                nextTick(() => {
                    titleInput.value?.focus();
                });
            } else {
                resetAutoSave();
            }
        } else {
            // Disable auto-save when closing
            autoSaveEnabled.value = false;
        }
    }
);

watch(
    () => props.note,
    (note) => {
        if (note && props.open && props.mode !== 'create') {
            currentNoteId.value = note.id;
            populateForm(note);
        }
    }
);

function resetForm() {
    form.value = {
        title: '',
        content: '',
        group_id: '',
        tag_ids: [],
        is_encrypted: false,
        encryption_password: '',
        encryption_hint: '',
        color: '',
        is_pinned: false,
    };
    errors.value = {};
    showOptions.value = false;
    currentNoteId.value = undefined;
}

function populateForm(note: NoteData) {
    form.value = {
        title: note.title || '',
        content: note.content || note.excerpt || '',
        group_id: note.group_id?.toString() || note.group?.id?.toString() || '',
        tag_ids: note.tag_ids || note.tags?.map((t) => t.id) || [],
        is_encrypted: note.is_encrypted || false,
        encryption_password: '',
        encryption_hint: '',
        color: note.color || '',
        is_pinned: note.is_pinned || false,
    };
}

function handleClose() {
    // Save any pending changes before closing
    if (isDirty.value && canSave.value) {
        autoSave();
    }
    clearLocal();
    isOpen.value = false;
}

function startEditing() {
    currentMode.value = 'edit';
    autoSaveEnabled.value = true;
    nextTick(() => {
        titleInput.value?.focus();
    });
}

async function handleSave() {
    if (!canSave.value || submitting.value) return;

    submitting.value = true;
    errors.value = {};

    try {
        if (isNewNote.value) {
            const response = await axios.post('/notes', form.value);
            currentNoteId.value = response.data.id;
            emit('saved');
        } else {
            await axios.put(`/notes/${currentNoteId.value}`, form.value);
            emit('saved');
        }
        clearLocal();
        resetAutoSave();
        handleClose();
    } catch (err: any) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            errors.value = { general: 'Failed to save note. Please try again.' };
        }
    } finally {
        submitting.value = false;
    }
}

function handleDelete() {
    if (!currentNoteId.value || submitting.value) return;

    if (confirm('Are you sure you want to delete this note?')) {
        submitting.value = true;
        router.delete(`/notes/${currentNoteId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('deleted');
                clearLocal();
                handleClose();
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
    }
}

function handleTogglePin() {
    form.value.is_pinned = !form.value.is_pinned;

    if (currentNoteId.value) {
        router.patch(
            `/notes/${currentNoteId.value}/pin`,
            {},
            { preserveScroll: true }
        );
    }
}

function handleToggleFavorite() {
    if (!currentNoteId.value) return;

    router.patch(
        `/notes/${currentNoteId.value}/favorite`,
        {},
        { preserveScroll: true }
    );
}

function handleArchive() {
    if (!currentNoteId.value) return;

    router.patch(
        `/notes/${currentNoteId.value}/archive`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
        }
    );
}

function handleShare() {
    if (props.note || currentNoteId.value) {
        emit('share', {
            ...props.note,
            id: currentNoteId.value,
            title: form.value.title,
            is_encrypted: form.value.is_encrypted,
            is_pinned: form.value.is_pinned,
            is_archived: props.note?.is_archived || false,
            is_favorited: props.note?.is_favorited || false,
            tags: props.note?.tags || [],
        });
    }
}
</script>

<template>
    <Dialog
        :open="isOpen"
        @update:open="isOpen = $event"
        :title="isNewNote ? 'New Note' : (isEditing ? 'Edit Note' : note?.title || 'Note')"
        size="lg"
    >
        <template #default>
            <div
                class="flex flex-col max-h-[70vh] -mx-6 -mt-2 px-6 pt-2 rounded-t-lg transition-colors"
                :style="modalBackground"
            >
                <!-- Auto-save status -->
                <div v-if="isEditing" class="flex items-center gap-2 mb-3 text-xs text-muted-foreground">
                    <Cloud v-if="!autoSaveError && (lastSaved || isSaving)" class="h-3 w-3" />
                    <CloudOff v-else-if="autoSaveError" class="h-3 w-3 text-destructive" />
                    <span :class="{ 'text-destructive': autoSaveError }">{{ saveStatusText }}</span>
                </div>

                <!-- Title -->
                <div class="mb-4">
                    <Input
                        ref="titleInput"
                        v-model="form.title"
                        placeholder="Title"
                        :disabled="!isEditing"
                        :error="errors.title"
                        :class="cn(
                            'text-lg font-medium border-0 shadow-none focus:ring-0 px-0 bg-transparent',
                            !isEditing && 'cursor-default'
                        )"
                        @click="!isEditing && startEditing()"
                    />
                </div>

                <!-- Content -->
                <div class="flex-1 min-h-[200px] mb-4">
                    <Textarea
                        v-model="form.content"
                        placeholder="Take a note..."
                        :disabled="!isEditing"
                        :rows="8"
                        :error="errors.content"
                        :class="cn(
                            'resize-none border-0 shadow-none focus:ring-0 px-0 bg-transparent h-full',
                            !isEditing && 'cursor-default'
                        )"
                        @click="!isEditing && startEditing()"
                    />
                </div>

                <!-- Tags Display -->
                <div v-if="form.tag_ids.length > 0" class="flex flex-wrap gap-1.5 mb-4">
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
                <div v-if="isEditing && showOptions" class="border-t border-border pt-4 space-y-4">
                    <!-- Group Selection -->
                    <div>
                        <Label class="text-xs text-muted-foreground mb-1.5 flex items-center gap-1">
                            <Folder class="h-3 w-3" />
                            Group
                        </Label>
                        <ComboBox
                            v-model="form.group_id"
                            :options="groupOptions"
                            placeholder="Select group..."
                        />
                    </div>

                    <!-- Tags Selection -->
                    <div>
                        <Label class="text-xs text-muted-foreground mb-1.5 flex items-center gap-1">
                            <Tag class="h-3 w-3" />
                            Tags
                        </Label>
                        <ComboBox
                            v-model="form.tag_ids"
                            :options="tagOptions"
                            placeholder="Select tags..."
                            multiple
                        />
                    </div>

                    <!-- Color Selection -->
                    <div>
                        <Label class="text-xs text-muted-foreground mb-1.5 flex items-center gap-1">
                            <Palette class="h-3 w-3" />
                            Background
                        </Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in colorOptions"
                                :key="color.value"
                                type="button"
                                :class="cn(
                                    'h-7 w-7 rounded-full border-2 transition-all',
                                    form.color === color.value
                                        ? 'border-primary ring-2 ring-primary/20'
                                        : 'border-transparent hover:border-muted-foreground/30',
                                    color.bg
                                )"
                                :style="color.value ? { backgroundColor: color.value } : {}"
                                :title="color.label"
                                @click="form.color = color.value"
                            />
                        </div>
                    </div>

                    <!-- Encryption Toggle -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Lock class="h-4 w-4 text-muted-foreground" />
                            <Label class="text-sm">Encrypt Note</Label>
                        </div>
                        <Toggle v-model="form.is_encrypted" />
                    </div>

                    <!-- Encryption Fields -->
                    <div v-if="form.is_encrypted && isNewNote" class="space-y-3 pl-6">
                        <Input
                            v-model="form.encryption_password"
                            type="password"
                            placeholder="Encryption code"
                            :error="errors.encryption_password"
                        />
                        <Input
                            v-model="form.encryption_hint"
                            placeholder="Hint (optional)"
                            :error="errors.encryption_hint"
                        />
                    </div>
                </div>

                <!-- Error display -->
                <div v-if="errors.general" class="mt-4 p-3 rounded-md bg-destructive/10 text-destructive text-sm">
                    {{ errors.general }}
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <!-- Left Actions -->
                <div class="flex items-center gap-1">
                    <Button
                        v-if="isEditing"
                        variant="ghost"
                        size="icon"
                        :class="form.is_pinned ? 'text-primary' : 'text-muted-foreground'"
                        @click="handleTogglePin"
                        title="Pin note"
                    >
                        <Pin class="h-4 w-4" :class="{ 'fill-current': form.is_pinned }" />
                    </Button>

                    <Button
                        v-if="currentNoteId"
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground"
                        @click="handleToggleFavorite"
                        title="Favorite"
                    >
                        <Star class="h-4 w-4" :class="{ 'fill-yellow-400 text-yellow-400': note?.is_favorited }" />
                    </Button>

                    <Button
                        v-if="currentNoteId"
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground"
                        @click="handleShare"
                        title="Share"
                    >
                        <Share2 class="h-4 w-4" />
                    </Button>

                    <Button
                        v-if="currentNoteId"
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground"
                        @click="handleArchive"
                        title="Archive"
                    >
                        <Archive class="h-4 w-4" />
                    </Button>

                    <Button
                        v-if="isEditing"
                        variant="ghost"
                        size="icon"
                        :class="showOptions ? 'text-primary' : 'text-muted-foreground'"
                        @click="showOptions = !showOptions"
                        title="More options"
                    >
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>

                    <Button
                        v-if="currentNoteId"
                        variant="ghost"
                        size="icon"
                        class="text-destructive"
                        @click="handleDelete"
                        :disabled="submitting"
                        title="Delete"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <Button variant="ghost" @click="handleClose">
                        Close
                    </Button>
                    <Button
                        v-if="isEditing"
                        @click="handleSave"
                        :disabled="!canSave || submitting"
                    >
                        <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        {{ submitting ? 'Saving...' : 'Save' }}
                    </Button>
                    <Button
                        v-else
                        @click="startEditing"
                    >
                        Edit
                    </Button>
                </div>
            </div>
        </template>
    </Dialog>
</template>
