<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardContent, Button, Input, Badge, Dialog, Label } from '@/Components/ui';
import { Plus, Edit, Trash2, Tag } from 'lucide-vue-next';

interface TagData {
    id: number;
    name: string;
    slug: string;
    color: string;
    notes_count: number;
}

const tags = ref<TagData[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingTag = ref<TagData | null>(null);
const deleteModal = ref(false);
const tagToDelete = ref<TagData | null>(null);
const submitting = ref(false);

const form = ref({
    name: '',
    color: '#3b82f6',
});

const errors = ref<Record<string, string>>({});

const colorOptions = [
    '#ef4444', '#f97316', '#eab308', '#22c55e',
    '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280',
];

const fetchTags = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/tags');
        const data = await response.json();
        if (data.success) {
            tags.value = data.data;
        }
    } catch (error) {
        console.error('Failed to fetch tags:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingTag.value = null;
    form.value = { name: '', color: '#3b82f6' };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (tag: TagData) => {
    editingTag.value = tag;
    form.value = { name: tag.name, color: tag.color };
    errors.value = {};
    showModal.value = true;
};

const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {};

    try {
        const url = editingTag.value ? `/api/tags/${editingTag.value.id}` : '/api/tags';
        const method = editingTag.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (data.success) {
            showModal.value = false;
            fetchTags();
        } else if (data.errors) {
            errors.value = data.errors;
        }
    } catch (error) {
        console.error('Failed to save tag:', error);
    } finally {
        submitting.value = false;
    }
};

const confirmDelete = (tag: TagData) => {
    tagToDelete.value = tag;
    deleteModal.value = true;
};

const handleDelete = async () => {
    if (!tagToDelete.value) return;

    try {
        const response = await fetch(`/api/tags/${tagToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (data.success) {
            deleteModal.value = false;
            tagToDelete.value = null;
            fetchTags();
        }
    } catch (error) {
        console.error('Failed to delete tag:', error);
    }
};

onMounted(() => {
    fetchTags();
});
</script>

<template>
    <Head title="Tags" />

    <AppLayout title="Tags">
        <div class="max-w-3xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <p class="text-muted-foreground">
                    Organize your notes with tags for quick filtering and discovery.
                </p>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    New Tag
                </Button>
            </div>

            <!-- Tags List -->
            <Card variant="outlined">
                <CardContent class="p-0">
                    <div v-if="loading" class="p-8 text-center text-muted-foreground">
                        Loading tags...
                    </div>

                    <div v-else-if="tags.length === 0" class="p-8 text-center">
                        <Tag class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-muted-foreground mb-4">No tags yet</p>
                        <Button @click="openCreateModal">
                            <Plus class="mr-2 h-4 w-4" />
                            Create your first tag
                        </Button>
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <Badge :color="tag.color" size="lg">
                                    {{ tag.name }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    {{ tag.notes_count }} {{ tag.notes_count === 1 ? 'note' : 'notes' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="icon" @click="openEditModal(tag)">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="confirmDelete(tag)">
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="showModal" :title="editingTag ? 'Edit Tag' : 'Create Tag'">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Enter tag name..."
                        :error="errors.name"
                        class="mt-1.5"
                    />
                </div>

                <div>
                    <Label>Color</Label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="color in colorOptions"
                            :key="color"
                            type="button"
                            class="h-8 w-8 rounded-full border-2 transition-all"
                            :class="[
                                form.color === color
                                    ? 'border-primary scale-110'
                                    : 'border-transparent hover:border-muted-foreground/50'
                            ]"
                            :style="{ backgroundColor: color }"
                            @click="form.color = color"
                        />
                    </div>
                </div>

                <div class="pt-2">
                    <p class="text-sm text-muted-foreground mb-2">Preview:</p>
                    <Badge :color="form.color" size="lg">
                        {{ form.name || 'Tag name' }}
                    </Badge>
                </div>
            </form>

            <template #footer>
                <Button variant="outline" @click="showModal = false">Cancel</Button>
                <Button :disabled="!form.name || submitting" @click="handleSubmit">
                    {{ submitting ? 'Saving...' : (editingTag ? 'Save Changes' : 'Create Tag') }}
                </Button>
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal" title="Delete Tag">
            <p class="text-muted-foreground">
                Are you sure you want to delete the tag "{{ tagToDelete?.name }}"?
                This will remove the tag from all notes.
            </p>
            <template #footer>
                <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                <Button variant="destructive" @click="handleDelete">Delete</Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
