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
    X,
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
    Loader2,
    Cloud,
    CloudOff,
    FileText,
    ChevronDown,
    ChevronUp,
    Check,
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

interface DefaultValues {
    is_encrypted?: boolean;
    is_favorited?: boolean;
    is_archived?: boolean;
    is_pinned?: boolean;
}

interface Props {
    open: boolean;
    note?: NoteData | null;
    tags: TagData[];
    groups: GroupData[];
    mode?: 'view' | 'edit' | 'create';
    defaultValues?: DefaultValues;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
    defaultValues: () => ({}),
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
const titleInput = ref<InstanceType<typeof Input> | null>(null);
const currentNoteId = ref<number | undefined>(undefined);

// Refs for ComboBox components
const tagComboBox = ref<InstanceType<typeof ComboBox> | null>(null);
const groupComboBox = ref<InstanceType<typeof ComboBox> | null>(null);

// Creating state for tags and groups
const creatingTag = ref(false);
const creatingGroup = ref(false);

// Local tags and groups (for when new ones are created)
const localTags = ref<TagData[]>([]);
const localGroups = ref<GroupData[]>([]);

// Core content form (auto-saved)
const contentForm = ref({
    title: '',
    content: '',
});

// Metadata form (saved separately via API)
const metaForm = ref({
    group_id: '',
    tag_ids: [] as number[],
    color: '',
    is_pinned: false,
    is_favorited: false,
    is_archived: false,
});

// Encryption form (only for new notes)
const encryptionForm = ref({
    is_encrypted: false,
    encryption_password: '',
    encryption_hint: '',
});

const autoSaveEnabled = ref(false);

// Auto-save functionality for core content only
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
    data: contentForm,
    storageKey: appConfig.storage.draftNote,
    enabled: autoSaveEnabled,
    onSave: async (data) => {
        return new Promise((resolve, reject) => {
            const url = currentNoteId.value ? `/notes/${currentNoteId.value}` : '/notes';
            const method = currentNoteId.value ? 'put' : 'post';

            // Only send core content data for auto-save
            const payload = {
                title: data.title,
                content: data.content,
            };

            router[method](url, payload, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: (page) => {
                    if (!currentNoteId.value && page.props.note) {
                        currentNoteId.value = (page.props.note as NoteData).id;
                    }
                    emit('saved');
                    resolve(undefined);
                },
                onError: (errors) => {
                    reject(new Error(Object.values(errors).join(', ')));
                },
            });
        });
    },
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

// Combine props tags with locally created tags
const allTags = computed(() => {
    const propTagIds = new Set(props.tags.map(t => t.id));
    const newTags = localTags.value.filter(t => !propTagIds.has(t.id));
    return [...props.tags, ...newTags];
});

// Combine props groups with locally created groups
const allGroups = computed(() => {
    const propGroupIds = new Set(props.groups.map(g => g.id));
    const newGroups = localGroups.value.filter(g => !propGroupIds.has(g.id));
    return [...props.groups, ...newGroups];
});

const tagOptions = computed(() =>
    allTags.value.map((tag) => ({
        value: tag.id,
        label: tag.name,
        color: tag.color,
    }))
);

const groupOptions = computed(() => [
    { value: '', label: 'No Group' },
    ...allGroups.value.map((group) => ({
        value: group.id.toString(),
        label: group.name,
        color: group.color,
    })),
]);

const isEditing = computed(() => currentMode.value === 'edit' || currentMode.value === 'create');
const isNewNote = computed(() => currentMode.value === 'create' && !currentNoteId.value);
const canSave = computed(() => contentForm.value.title.trim().length > 0);

// Convert hex to RGB for light background
const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

const modalBackground = computed(() => {
    if (metaForm.value.color) {
        const rgb = hexToRgb(metaForm.value.color);
        if (rgb) {
            return { backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.12)` };
        }
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
    contentForm.value = {
        title: '',
        content: '',
    };
    metaForm.value = {
        group_id: '',
        tag_ids: [],
        color: '',
        is_pinned: props.defaultValues?.is_pinned ?? false,
        is_favorited: props.defaultValues?.is_favorited ?? false,
        is_archived: props.defaultValues?.is_archived ?? false,
    };
    encryptionForm.value = {
        is_encrypted: props.defaultValues?.is_encrypted ?? false,
        encryption_password: '',
        encryption_hint: '',
    };
    errors.value = {};
    // Auto-show options panel if encryption is enabled by default
    showOptions.value = props.defaultValues?.is_encrypted ?? false;
    currentNoteId.value = undefined;
    // Reset local tags/groups
    localTags.value = [];
    localGroups.value = [];
}

function populateForm(note: NoteData) {
    contentForm.value = {
        title: note.title || '',
        content: note.content || note.excerpt || '',
    };
    metaForm.value = {
        group_id: note.group_id?.toString() || note.group?.id?.toString() || '',
        tag_ids: note.tag_ids || note.tags?.map((t) => t.id) || [],
        color: note.color || '',
        is_pinned: note.is_pinned || false,
        is_favorited: note.is_favorited || false,
        is_archived: note.is_archived || false,
    };
    encryptionForm.value = {
        is_encrypted: note.is_encrypted || false,
        encryption_password: '',
        encryption_hint: '',
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

// API calls for metadata updates
async function updateColor(color: string) {
    metaForm.value.color = color;
    if (currentNoteId.value) {
        try {
            await axios.patch(`/api/notes/${currentNoteId.value}/color`, { color });
        } catch (err) {
            console.error('Failed to update color:', err);
        }
    }
}

async function updateTags(tagIds: string | number | (string | number)[]) {
    const ids = Array.isArray(tagIds) ? tagIds.map(Number) : [Number(tagIds)];
    metaForm.value.tag_ids = ids;
    if (currentNoteId.value) {
        try {
            await axios.patch(`/api/notes/${currentNoteId.value}/tags`, { tag_ids: ids });
            emit('saved');
        } catch (err) {
            console.error('Failed to update tags:', err);
        }
    }
}

async function updateGroup(groupId: string | number | (string | number)[]) {
    const id = String(groupId);
    metaForm.value.group_id = id;
    if (currentNoteId.value) {
        try {
            if (id) {
                await axios.post(`/api/notes/${currentNoteId.value}/add-to-group`, { group_id: parseInt(id) });
            } else {
                await axios.post(`/api/notes/${currentNoteId.value}/remove-from-group`);
            }
            emit('saved');
        } catch (err) {
            console.error('Failed to update group:', err);
        }
    }
}

// Generate a random color for new tags
function generateRandomColor(): string {
    const colors = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
        '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e',
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Create a new tag
async function handleCreateTag(name: string) {
    if (creatingTag.value) return;

    creatingTag.value = true;
    try {
        const response = await axios.post('/api/tags', {
            name,
            color: generateRandomColor(),
        });

        if (response.data.success) {
            const newTag = response.data.data as TagData;
            localTags.value.push(newTag);

            // Add the new tag to selected tags
            metaForm.value.tag_ids = [...metaForm.value.tag_ids, newTag.id];

            // Update the note if it exists
            if (currentNoteId.value) {
                await axios.patch(`/api/notes/${currentNoteId.value}/tags`, {
                    tag_ids: metaForm.value.tag_ids
                });
                emit('saved');
            }

            // Clear the search
            tagComboBox.value?.clearSearch();
        }
    } catch (err) {
        console.error('Failed to create tag:', err);
    } finally {
        creatingTag.value = false;
    }
}

// Create a new group
async function handleCreateGroup(name: string) {
    if (creatingGroup.value) return;

    creatingGroup.value = true;
    try {
        const response = await axios.post('/api/groups', {
            name,
        });

        if (response.data.success) {
            const newGroup = response.data.data as GroupData;
            localGroups.value.push(newGroup);

            // Select the new group
            metaForm.value.group_id = newGroup.id.toString();

            // Update the note if it exists
            if (currentNoteId.value) {
                await axios.post(`/api/notes/${currentNoteId.value}/add-to-group`, {
                    group_id: newGroup.id
                });
                emit('saved');
            }

            // Clear the search
            groupComboBox.value?.clearSearch();
        }
    } catch (err) {
        console.error('Failed to create group:', err);
    } finally {
        creatingGroup.value = false;
    }
}

async function handleSave() {
    if (!canSave.value || submitting.value) return;

    // Ensure we have a note ID when editing an existing note
    const noteId = currentNoteId.value || props.note?.id;
    const isCreating = currentMode.value === 'create' && !noteId;

    submitting.value = true;
    errors.value = {};

    const url = isCreating ? '/notes' : `/notes/${noteId}`;
    const method = isCreating ? 'post' : 'put';

    // Combine all form data for full save
    // Include encryption data if enabling encryption (for new notes OR converting existing note to encrypted)
    const includeEncryption = encryptionForm.value.is_encrypted &&
        (isCreating || !props.note?.is_encrypted);

    const payload = {
        ...contentForm.value,
        ...metaForm.value,
        ...(includeEncryption ? encryptionForm.value : {}),
    };

    router[method](url, payload, {
        preserveScroll: true,
        onSuccess: (page) => {
            // For new notes, get the ID from the response
            if (isCreating && page.props.note) {
                currentNoteId.value = (page.props.note as NoteData).id;
            }
            emit('saved');
            clearLocal();
            resetAutoSave();
            handleClose();
        },
        onError: (errs) => {
            errors.value = errs as Record<string, string>;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
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

async function handleTogglePin() {
    metaForm.value.is_pinned = !metaForm.value.is_pinned;

    if (currentNoteId.value) {
        try {
            await axios.post(`/api/notes/${currentNoteId.value}/toggle-pin`);
            emit('saved');
        } catch (err) {
            console.error('Failed to toggle pin:', err);
            metaForm.value.is_pinned = !metaForm.value.is_pinned; // Revert on error
        }
    }
}

async function handleToggleFavorite() {
    if (!currentNoteId.value) return;

    try {
        await axios.post(`/api/notes/${currentNoteId.value}/toggle-favorite`);
        emit('saved');
    } catch (err) {
        console.error('Failed to toggle favorite:', err);
    }
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
            title: contentForm.value.title,
            is_encrypted: encryptionForm.value.is_encrypted,
            is_pinned: metaForm.value.is_pinned,
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
        size="2xl"
        hide-close
        class="sm:max-w-2xl"
    >
        <template #default>
            <div class="flex flex-col max-h-[90vh] sm:max-h-[80vh] -m-6">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-border">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex items-center justify-center w-10 h-10 rounded-xl bg-primary/10 text-primary">
                            <FileText class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-semibold text-foreground">
                                {{ isNewNote ? 'New Note' : (isEditing ? 'Edit Note' : 'View Note') }}
                            </h2>
                            <!-- Auto-save status -->
                            <div v-if="isEditing" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Cloud v-if="!autoSaveError && (lastSaved || isSaving)" class="h-3 w-3" />
                                <CloudOff v-else-if="autoSaveError" class="h-3 w-3 text-destructive" />
                                <span :class="{ 'text-destructive': autoSaveError }">{{ saveStatusText }}</span>
                            </div>
                        </div>
                    </div>
                    <Button variant="ghost" size="icon" @click="handleClose">
                        <X class="w-5 h-5" />
                    </Button>
                </div>

                <!-- Content Area -->
                <div
                    class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-5 transition-colors"
                    :style="modalBackground"
                >
                    <!-- Title -->
                    <div class="mb-3 sm:mb-4">
                        <Input
                            ref="titleInput"
                            v-model="contentForm.title"
                            placeholder="Give your note a title..."
                            :disabled="!isEditing"
                            :error="errors.title"
                            :class="cn(
                                'text-xl sm:text-2xl font-semibold border-0 shadow-none px-0 bg-transparent h-auto',
                                !isEditing && 'cursor-default'
                            )"
                            @click="!isEditing && startEditing()"
                        />
                    </div>

                    <!-- Content -->
                    <div class="min-h-[150px] sm:min-h-[200px] mb-4">
                        <Textarea
                            v-model="contentForm.content"
                            placeholder="Start writing..."
                            :disabled="!isEditing"
                            :rows="8"
                            :error="errors.content"
                            :class="cn(
                                'resize-none border-0 shadow-none px-0 bg-transparent text-base leading-relaxed',
                                !isEditing && 'cursor-default'
                            )"
                            @click="!isEditing && startEditing()"
                        />
                    </div>

                    <!-- Tags Display -->
                    <div v-if="metaForm.tag_ids.length > 0" class="flex flex-wrap gap-1.5 mb-4">
                        <Badge
                            v-for="tagId in metaForm.tag_ids"
                            :key="tagId"
                            :color="allTags.find(t => t.id === tagId)?.color"
                            size="sm"
                        >
                            {{ allTags.find(t => t.id === tagId)?.name }}
                        </Badge>
                    </div>

                    <!-- Options Panel (collapsible) -->
                    <div v-if="isEditing" class="border-t border-border/50 pt-4">
                        <button
                            type="button"
                            class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors mb-4"
                            @click="showOptions = !showOptions"
                        >
                            <component :is="showOptions ? ChevronUp : ChevronDown" class="w-4 h-4" />
                            {{ showOptions ? 'Hide options' : 'More options' }}
                        </button>

                        <div v-if="showOptions" class="space-y-5 animate-in slide-in-from-top-2 duration-200">
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
                                            'h-8 w-8 rounded-full border-2 transition-all',
                                            metaForm.color === color.value
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

                            <!-- Group Selection -->
                            <div>
                                <Label class="text-xs text-muted-foreground mb-2 flex items-center gap-1.5">
                                    <Folder class="h-3.5 w-3.5" />
                                    Group
                                </Label>
                                <ComboBox
                                    ref="groupComboBox"
                                    :model-value="metaForm.group_id"
                                    :options="groupOptions"
                                    placeholder="Select a group..."
                                    creatable
                                    create-label="Create group"
                                    :creating="creatingGroup"
                                    @update:model-value="updateGroup"
                                    @create="handleCreateGroup"
                                />
                            </div>

                            <!-- Tags Selection -->
                            <div>
                                <Label class="text-xs text-muted-foreground mb-2 flex items-center gap-1.5">
                                    <Tag class="h-3.5 w-3.5" />
                                    Tags
                                </Label>
                                <ComboBox
                                    ref="tagComboBox"
                                    :model-value="metaForm.tag_ids"
                                    :options="tagOptions"
                                    placeholder="Select tags..."
                                    multiple
                                    creatable
                                    create-label="Create tag"
                                    :creating="creatingTag"
                                    @update:model-value="updateTags"
                                    @create="handleCreateTag"
                                />
                            </div>

                            <!-- Encryption Section (for new notes OR non-encrypted existing notes) -->
                            <div v-if="isNewNote || !props.note?.is_encrypted" class="space-y-3">
                                <div class="flex items-center justify-between py-2">
                                    <div class="flex items-center gap-2">
                                        <Lock class="h-4 w-4 text-muted-foreground" />
                                        <Label class="text-sm font-normal">Encrypt this note</Label>
                                    </div>
                                    <Toggle v-model="encryptionForm.is_encrypted" />
                                </div>

                                <!-- Encryption Info Card -->
                                <div v-if="encryptionForm.is_encrypted" class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30 p-3 sm:p-4">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <Lock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                        </div>
                                        <div class="space-y-2 text-sm">
                                            <p class="font-medium text-amber-800 dark:text-amber-200">About Encrypted Notes</p>
                                            <ul class="space-y-1 text-amber-700 dark:text-amber-300 list-disc list-inside">
                                                <li>Content is encrypted with your code and cannot be recovered without it</li>
                                                <li>Encrypted notes display a blur effect - the actual content is never shown</li>
                                                <li>Use the hint to help remember your encryption code</li>
                                                <li>We cannot recover your content if you forget the code</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Encryption Fields -->
                                <div v-if="encryptionForm.is_encrypted" class="space-y-3 pl-6">
                                    <div class="space-y-1.5">
                                        <Label class="text-sm">
                                            Encryption Code
                                            <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            v-model="encryptionForm.encryption_password"
                                            type="password"
                                            placeholder="Enter a secure code (min 4 characters)"
                                            :error="errors.encryption_password"
                                            required
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-sm text-muted-foreground">Hint (optional)</Label>
                                        <Input
                                            v-model="encryptionForm.encryption_hint"
                                            placeholder="Helps you remember the code"
                                            :error="errors.encryption_hint"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Encrypted note indicator (for existing encrypted notes) -->
                            <div v-if="!isNewNote && props.note?.is_encrypted" class="rounded-lg border border-muted bg-muted/30 p-3 sm:p-4">
                                <div class="flex items-center gap-3">
                                    <Lock class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">This note is encrypted</p>
                                        <p class="text-xs text-muted-foreground">Content is hidden and requires your encryption code to view</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error display -->
                    <div v-if="errors.general" class="mt-4 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                        {{ errors.general }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-border px-4 sm:px-6 py-3 sm:py-4 bg-muted/30">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0">
                        <!-- Left Actions - scrollable on mobile -->
                        <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0 -mx-1 px-1">
                            <Button
                                v-if="isEditing"
                                variant="ghost"
                                size="sm"
                                :class="metaForm.is_pinned ? 'text-primary' : 'text-muted-foreground'"
                                @click="handleTogglePin"
                                title="Pin note"
                            >
                                <Pin class="h-4 w-4 sm:mr-1.5" :class="{ 'fill-current': metaForm.is_pinned }" />
                                <span class="hidden sm:inline">{{ metaForm.is_pinned ? 'Pinned' : 'Pin' }}</span>
                            </Button>

                            <Button
                                v-if="currentNoteId"
                                variant="ghost"
                                size="sm"
                                class="text-muted-foreground"
                                @click="handleToggleFavorite"
                                title="Favorite"
                            >
                                <Star class="h-4 w-4 sm:mr-1.5" :class="{ 'fill-yellow-400 text-yellow-400': note?.is_favorited }" />
                                <span class="hidden sm:inline">Favorite</span>
                            </Button>

                            <Button
                                v-if="currentNoteId"
                                variant="ghost"
                                size="sm"
                                class="text-muted-foreground"
                                @click="handleShare"
                                title="Share"
                            >
                                <Share2 class="h-4 w-4 sm:mr-1.5" />
                                <span class="hidden sm:inline">Share</span>
                            </Button>

                            <Button
                                v-if="currentNoteId"
                                variant="ghost"
                                size="sm"
                                class="text-muted-foreground"
                                @click="handleArchive"
                                title="Archive"
                            >
                                <Archive class="h-4 w-4 sm:mr-1.5" />
                                <span class="hidden sm:inline">Archive</span>
                            </Button>

                            <Button
                                v-if="currentNoteId"
                                variant="ghost"
                                size="sm"
                                class="text-destructive"
                                @click="handleDelete"
                                :disabled="submitting"
                                title="Delete"
                            >
                                <Trash2 class="h-4 w-4 sm:mr-1.5" />
                                <span class="hidden sm:inline">Delete</span>
                            </Button>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" class="sm:size-default" @click="handleClose">
                                Cancel
                            </Button>
                            <Button
                                v-if="isEditing"
                                size="sm"
                                class="sm:size-default"
                                @click="handleSave"
                                :disabled="!canSave || submitting"
                            >
                                <Loader2 v-if="submitting" class="mr-1.5 sm:mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-1.5 sm:mr-2 h-4 w-4" />
                                <span class="hidden sm:inline">{{ submitting ? 'Saving...' : 'Save Note' }}</span>
                                <span class="sm:hidden">Save</span>
                            </Button>
                            <Button
                                v-else
                                size="sm"
                                class="sm:size-default"
                                @click="startEditing"
                            >
                                <span class="hidden sm:inline">Edit Note</span>
                                <span class="sm:hidden">Edit</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>
