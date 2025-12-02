<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardContent, Button, Input, Textarea, Label, Select, Badge, Toggle } from '@/Components/ui';
import { Save, ArrowLeft, Lock, Pin, Tag, Folder, Palette, X } from 'lucide-vue-next';

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

interface Props {
    tags: TagData[];
    groups: GroupData[];
}

const props = defineProps<Props>();

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

    router.post('/notes', form.value, {
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const goBack = () => {
    router.visit('/notes');
};
</script>

<template>
    <Head title="Create Note" />

    <AppLayout title="Create Note">
        <form @submit.prevent="submit" class="max-w-4xl">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <Button type="button" variant="ghost" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back
                </Button>
                <Button type="submit" :disabled="submitting">
                    <Save class="mr-2 h-4 w-4" />
                    {{ submitting ? 'Saving...' : 'Save Note' }}
                </Button>
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
                                <Label for="content">Content</Label>
                                <Textarea
                                    id="content"
                                    v-model="form.content"
                                    placeholder="Write your note content..."
                                    :rows="12"
                                    :error="errors.content"
                                    class="mt-1.5"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Encryption Section -->
                    <Card v-if="form.is_encrypted" variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Lock class="h-4 w-4 text-yellow-500" />
                                Encryption Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="encryption_password">Encryption Code</Label>
                                <Input
                                    id="encryption_password"
                                    v-model="form.encryption_password"
                                    type="password"
                                    placeholder="Enter encryption code..."
                                    :error="errors.encryption_password"
                                    class="mt-1.5"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    This code will be required to view the note content. Keep it safe!
                                </p>
                            </div>

                            <div>
                                <Label for="encryption_hint">Hint (Optional)</Label>
                                <Input
                                    id="encryption_hint"
                                    v-model="form.encryption_hint"
                                    placeholder="Enter a hint to remember your code..."
                                    :error="errors.encryption_hint"
                                    class="mt-1.5"
                                />
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

                            <!-- Encrypt Toggle -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Lock class="h-4 w-4 text-muted-foreground" />
                                    <Label>Encrypt Note</Label>
                                </div>
                                <Toggle v-model="form.is_encrypted" />
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
