<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardContent, Button, Input, Textarea, Label, Select, Badge, Toggle } from '@/Components/ui';
import { Save, ArrowLeft, Lock, Pin, Tag, Folder, Palette, X, Trash2 } from 'lucide-vue-next';

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
    children?: GroupData[];
}

interface NoteData {
    id: number;
    title: string;
    slug: string;
    content?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    group_id?: number;
    tags: TagData[];
}

interface Props {
    note: NoteData;
    tags: TagData[];
    groups: GroupData[];
}

const props = defineProps<Props>();

const form = ref({
    title: props.note.title,
    content: props.note.content || '',
    group_id: props.note.group_id?.toString() || '',
    tag_ids: props.note.tags.map(t => t.id),
    color: props.note.color || '',
    is_pinned: props.note.is_pinned,
});

const errors = ref<Record<string, string>>({});
const submitting = ref(false);

const colorOptions = [
    { value: '', label: 'None' },
    { value: '#ef4444', label: 'Red' },
    { value: '#f97316', label: 'Orange' },
    { value: '#eab308', label: 'Yellow' },
    { value: '#22c55e', label: 'Green' },
    { value: '#3b82f6', label: 'Blue' },
    { value: '#8b5cf6', label: 'Purple' },
    { value: '#ec4899', label: 'Pink' },
];

const flatGroups = computed(() => {
    const flatten = (groups: GroupData[], depth = 0): { group: GroupData; depth: number }[] => {
        const result: { group: GroupData; depth: number }[] = [];
        for (const group of groups) {
            result.push({ group, depth });
            if (group.children?.length) {
                result.push(...flatten(group.children, depth + 1));
            }
        }
        return result;
    };
    return flatten(props.groups);
});

const toggleTag = (tagId: number) => {
    const index = form.value.tag_ids.indexOf(tagId);
    if (index === -1) {
        form.value.tag_ids.push(tagId);
    } else {
        form.value.tag_ids.splice(index, 1);
    }
};

const isTagSelected = (tagId: number) => form.value.tag_ids.includes(tagId);

const submit = () => {
    submitting.value = true;
    errors.value = {};

    router.put(`/notes/${props.note.id}`, form.value, {
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const goBack = () => {
    router.visit(`/notes/${props.note.id}`);
};

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this note?')) {
        router.delete(`/notes/${props.note.id}`);
    }
};
</script>

<template>
    <Head :title="`Edit: ${note.title}`" />

    <AppLayout :title="`Edit: ${note.title}`">
        <form @submit.prevent="submit" class="max-w-4xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Button type="button" variant="ghost" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back
                </Button>
                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="handleDelete">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                    <Button type="submit" :disabled="submitting">
                        <Save class="mr-2 h-4 w-4" />
                        {{ submitting ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <Card variant="outlined">
                        <CardContent class="p-6 space-y-4">
                            <!-- Title -->
                            <div>
                                <Label for="title">Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Enter note title..."
                                    :error="errors.title"
                                    class="mt-1.5"
                                />
                            </div>

                            <!-- Content -->
                            <div>
                                <Label for="content">
                                    Content
                                    <span v-if="note.is_encrypted" class="ml-2 text-xs text-yellow-500">
                                        (Encrypted - content cannot be edited)
                                    </span>
                                </Label>
                                <Textarea
                                    id="content"
                                    v-model="form.content"
                                    placeholder="Write your note content..."
                                    :rows="12"
                                    :error="errors.content"
                                    :disabled="note.is_encrypted"
                                    class="mt-1.5"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Encryption Notice -->
                    <Card v-if="note.is_encrypted" variant="flat" class="border-yellow-500/20 bg-yellow-500/5">
                        <CardContent class="p-4 flex items-center gap-3">
                            <Lock class="h-5 w-5 text-yellow-500" />
                            <div>
                                <p class="font-medium text-yellow-600 dark:text-yellow-400">
                                    This note is encrypted
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Encrypted content cannot be edited. You can modify the title, tags, and other metadata.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Options -->
                    <Card variant="outlined">
                        <CardHeader>
                            <CardTitle class="text-base">Options</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Pin Toggle -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Pin class="h-4 w-4 text-muted-foreground" />
                                    <Label>Pin Note</Label>
                                </div>
                                <Toggle v-model="form.is_pinned" />
                            </div>

                            <!-- Color -->
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <Palette class="h-4 w-4 text-muted-foreground" />
                                    <Label>Color</Label>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="color in colorOptions"
                                        :key="color.value"
                                        type="button"
                                        class="h-6 w-6 rounded-full border-2 transition-all"
                                        :class="[
                                            form.color === color.value
                                                ? 'border-primary scale-110'
                                                : 'border-transparent hover:border-muted-foreground/50'
                                        ]"
                                        :style="{ backgroundColor: color.value || 'var(--muted)' }"
                                        :title="color.label"
                                        @click="form.color = color.value"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Group -->
                    <Card variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Folder class="h-4 w-4" />
                                Group
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Select v-model="form.group_id">
                                <option value="">No Group</option>
                                <option
                                    v-for="{ group, depth } in flatGroups"
                                    :key="group.id"
                                    :value="group.id.toString()"
                                >
                                    {{ '—'.repeat(depth) }} {{ group.name }}
                                </option>
                            </Select>
                        </CardContent>
                    </Card>

                    <!-- Tags -->
                    <Card variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Tag class="h-4 w-4" />
                                Tags
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="tags.length" class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    :color="isTagSelected(tag.id) ? tag.color : undefined"
                                    :variant="isTagSelected(tag.id) ? 'default' : 'outline'"
                                    class="cursor-pointer transition-all"
                                    @click="toggleTag(tag.id)"
                                >
                                    {{ tag.name }}
                                    <X v-if="isTagSelected(tag.id)" class="ml-1 h-3 w-3" />
                                </Badge>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                No tags available. Create tags first.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
