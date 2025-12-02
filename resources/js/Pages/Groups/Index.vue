<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardContent, Button, Input, Dialog, Label, Select } from '@/Components/ui';
import { Plus, Edit, Trash2, Folder, ChevronRight } from 'lucide-vue-next';

interface GroupData {
    id: number;
    name: string;
    slug: string;
    color?: string;
    parent_id?: number;
    notes_count: number;
    children?: GroupData[];
}

const groups = ref<GroupData[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingGroup = ref<GroupData | null>(null);
const deleteModal = ref(false);
const groupToDelete = ref<GroupData | null>(null);
const submitting = ref(false);

const form = ref({
    name: '',
    color: '',
    parent_id: '',
});

const errors = ref<Record<string, string>>({});

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

const fetchGroups = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/groups');
        const data = await response.json();
        if (data.success) {
            groups.value = data.data;
        }
    } catch (error) {
        console.error('Failed to fetch groups:', error);
    } finally {
        loading.value = false;
    }
};

const flatGroups = (groups: GroupData[], excludeId?: number): GroupData[] => {
    const result: GroupData[] = [];
    const flatten = (items: GroupData[]) => {
        for (const item of items) {
            if (item.id !== excludeId) {
                result.push(item);
            }
            if (item.children?.length) {
                flatten(item.children);
            }
        }
    };
    flatten(groups);
    return result;
};

const openCreateModal = () => {
    editingGroup.value = null;
    form.value = { name: '', color: '', parent_id: '' };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (group: GroupData) => {
    editingGroup.value = group;
    form.value = {
        name: group.name,
        color: group.color || '',
        parent_id: group.parent_id?.toString() || '',
    };
    errors.value = {};
    showModal.value = true;
};

const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {};

    try {
        const url = editingGroup.value ? `/api/groups/${editingGroup.value.id}` : '/api/groups';
        const method = editingGroup.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                ...form.value,
                parent_id: form.value.parent_id || null,
                color: form.value.color || null,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showModal.value = false;
            fetchGroups();
        } else if (data.errors) {
            errors.value = data.errors;
        }
    } catch (error) {
        console.error('Failed to save group:', error);
    } finally {
        submitting.value = false;
    }
};

const confirmDelete = (group: GroupData) => {
    groupToDelete.value = group;
    deleteModal.value = true;
};

const handleDelete = async () => {
    if (!groupToDelete.value) return;

    try {
        const response = await fetch(`/api/groups/${groupToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (data.success) {
            deleteModal.value = false;
            groupToDelete.value = null;
            fetchGroups();
        }
    } catch (error) {
        console.error('Failed to delete group:', error);
    }
};

onMounted(() => {
    fetchGroups();
});
</script>

<template>
    <Head title="Groups" />

    <AppLayout title="Groups">
        <div class="max-w-3xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <p class="text-muted-foreground">
                    Organize your notes into groups and subgroups for better structure.
                </p>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    New Group
                </Button>
            </div>

            <!-- Groups List -->
            <Card variant="outlined">
                <CardContent class="p-0">
                    <div v-if="loading" class="p-8 text-center text-muted-foreground">
                        Loading groups...
                    </div>

                    <div v-else-if="groups.length === 0" class="p-8 text-center">
                        <Folder class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-muted-foreground mb-4">No groups yet</p>
                        <Button @click="openCreateModal">
                            <Plus class="mr-2 h-4 w-4" />
                            Create your first group
                        </Button>
                    </div>

                    <div v-else class="divide-y">
                        <template v-for="group in groups" :key="group.id">
                            <!-- Parent Group -->
                            <div class="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-md"
                                        :style="{ backgroundColor: group.color || 'var(--muted)' }"
                                    >
                                        <Folder class="h-4 w-4 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ group.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ group.notes_count }} {{ group.notes_count === 1 ? 'note' : 'notes' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(group)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(group)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Child Groups -->
                            <div
                                v-for="child in group.children"
                                :key="child.id"
                                class="flex items-center justify-between p-4 pl-12 hover:bg-muted/50 transition-colors border-l-2 border-muted ml-4"
                            >
                                <div class="flex items-center gap-3">
                                    <ChevronRight class="h-4 w-4 text-muted-foreground" />
                                    <div
                                        class="flex h-6 w-6 items-center justify-center rounded-md"
                                        :style="{ backgroundColor: child.color || 'var(--muted)' }"
                                    >
                                        <Folder class="h-3 w-3 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm">{{ child.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ child.notes_count }} {{ child.notes_count === 1 ? 'note' : 'notes' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(child)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(child)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="showModal" :title="editingGroup ? 'Edit Group' : 'Create Group'">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Enter group name..."
                        :error="errors.name"
                        class="mt-1.5"
                    />
                </div>

                <div>
                    <Label for="parent">Parent Group (Optional)</Label>
                    <Select v-model="form.parent_id" class="mt-1.5">
                        <option value="">No parent (root level)</option>
                        <option
                            v-for="group in flatGroups(groups, editingGroup?.id)"
                            :key="group.id"
                            :value="group.id.toString()"
                        >
                            {{ group.name }}
                        </option>
                    </Select>
                </div>

                <div>
                    <Label>Color (Optional)</Label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="color in colorOptions"
                            :key="color.value"
                            type="button"
                            class="h-8 w-8 rounded-md border-2 transition-all"
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
            </form>

            <template #footer>
                <Button variant="outline" @click="showModal = false">Cancel</Button>
                <Button :disabled="!form.name || submitting" @click="handleSubmit">
                    {{ submitting ? 'Saving...' : (editingGroup ? 'Save Changes' : 'Create Group') }}
                </Button>
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal" title="Delete Group">
            <p class="text-muted-foreground">
                Are you sure you want to delete the group "{{ groupToDelete?.name }}"?
                Notes in this group will be moved to the root level.
            </p>
            <template #footer>
                <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                <Button variant="destructive" @click="handleDelete">Delete</Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
