<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardContent, Button, Badge, Input, Dialog } from '@/Components/ui';
import { formatRelativeTime, formatDate } from '@/lib/utils';
import {
    ArrowLeft, Edit, Trash2, Archive, ArchiveRestore, Pin, PinOff,
    Star, Share2, Lock, Unlock, Eye, EyeOff, Tag, Folder, Clock, Calendar
} from 'lucide-vue-next';

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
}

interface ShareData {
    id: number;
    permission: 'view' | 'edit';
    expires_at?: string;
    shared_with_user: {
        id: number;
        name: string;
        email: string;
    };
}

interface NoteData {
    id: number;
    title: string;
    slug: string;
    content?: string;
    excerpt?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    views_count: number;
    created_at: string;
    updated_at: string;
    archived_at?: string;
    group?: GroupData;
    tags: TagData[];
    shares?: ShareData[];
}

interface Props {
    note: NoteData;
    isEncrypted: boolean;
    uniqueCode?: string;
}

const props = defineProps<Props>();

const decryptedContent = ref<string | null>(null);
const decryptionCode = ref('');
const decryptError = ref('');
const decrypting = ref(false);
const showDecryptDialog = ref(false);
const deleteModal = ref(false);

const displayContent = computed(() => {
    if (props.note.is_encrypted) {
        return decryptedContent.value;
    }
    return props.note.content;
});

const handleDecrypt = async () => {
    decrypting.value = true;
    decryptError.value = '';

    try {
        const response = await fetch(`/api/notes/${props.note.id}/decrypt`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ code: decryptionCode.value }),
        });

        const data = await response.json();

        if (data.success) {
            decryptedContent.value = data.data.content;
            showDecryptDialog.value = false;
            decryptionCode.value = '';
        } else {
            decryptError.value = data.message || 'Invalid code';
        }
    } catch (error) {
        decryptError.value = 'Failed to decrypt note';
    } finally {
        decrypting.value = false;
    }
};

const handleTogglePin = () => {
    router.patch(`/notes/${props.note.id}/pin`, {}, {
        preserveScroll: true,
    });
};

const handleToggleFavorite = () => {
    router.patch(`/notes/${props.note.id}/favorite`, {}, {
        preserveScroll: true,
    });
};

const handleArchive = () => {
    router.patch(`/notes/${props.note.id}/archive`, {}, {
        preserveScroll: true,
    });
};

const handleDelete = () => {
    router.delete(`/notes/${props.note.id}`);
};

const goBack = () => {
    router.visit('/notes');
};
</script>

<template>
    <Head :title="note.title" />

    <AppLayout :title="note.title">
        <div class="max-w-4xl">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Button variant="ghost" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Notes
                </Button>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        :title="note.is_pinned ? 'Unpin' : 'Pin'"
                        @click="handleTogglePin"
                    >
                        <PinOff v-if="note.is_pinned" class="h-4 w-4" />
                        <Pin v-else class="h-4 w-4" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        :title="note.is_favorited ? 'Remove from favorites' : 'Add to favorites'"
                        @click="handleToggleFavorite"
                    >
                        <Star
                            class="h-4 w-4"
                            :class="note.is_favorited ? 'fill-yellow-400 text-yellow-400' : ''"
                        />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        :title="note.is_archived ? 'Unarchive' : 'Archive'"
                        @click="handleArchive"
                    >
                        <ArchiveRestore v-if="note.is_archived" class="h-4 w-4" />
                        <Archive v-else class="h-4 w-4" />
                    </Button>

                    <Link :href="`/notes/${note.id}/edit`">
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </Link>

                    <Button variant="outline" @click="deleteModal = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <Card
                        variant="outlined"
                        :style="note.color ? { borderLeftColor: note.color, borderLeftWidth: '4px' } : {}"
                    >
                        <CardHeader>
                            <div class="flex items-center gap-2 flex-wrap">
                                <Pin v-if="note.is_pinned" class="h-4 w-4 text-accent" />
                                <Lock v-if="note.is_encrypted" class="h-4 w-4 text-yellow-500" />
                                <Star v-if="note.is_favorited" class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                <CardTitle>{{ note.title }}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Encrypted Content -->
                            <div v-if="note.is_encrypted && !decryptedContent" class="text-center py-8">
                                <Lock class="mx-auto h-12 w-12 text-yellow-500 mb-4" />
                                <p class="text-muted-foreground mb-4">
                                    This note is encrypted. Enter your code to view the content.
                                </p>
                                <Button @click="showDecryptDialog = true">
                                    <Unlock class="mr-2 h-4 w-4" />
                                    Decrypt Note
                                </Button>
                            </div>

                            <!-- Note Content -->
                            <div v-else-if="displayContent" class="prose prose-sm dark:prose-invert max-w-none">
                                <pre class="whitespace-pre-wrap font-sans">{{ displayContent }}</pre>
                            </div>

                            <p v-else class="text-muted-foreground italic">
                                No content
                            </p>

                            <!-- Hide decrypted content button -->
                            <Button
                                v-if="decryptedContent"
                                variant="ghost"
                                size="sm"
                                class="mt-4"
                                @click="decryptedContent = null"
                            >
                                <EyeOff class="mr-2 h-4 w-4" />
                                Hide Content
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Metadata -->
                    <Card variant="outlined">
                        <CardHeader>
                            <CardTitle class="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Calendar class="h-4 w-4" />
                                <span>Created {{ formatDate(note.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Clock class="h-4 w-4" />
                                <span>Updated {{ formatRelativeTime(note.updated_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Eye class="h-4 w-4" />
                                <span>{{ note.views_count }} views</span>
                            </div>
                            <div v-if="note.is_archived" class="flex items-center gap-2 text-yellow-600">
                                <Archive class="h-4 w-4" />
                                <span>Archived {{ formatRelativeTime(note.archived_at!) }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Group -->
                    <Card v-if="note.group" variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Folder class="h-4 w-4" />
                                Group
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="note.group.color"
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: note.group.color }"
                                />
                                <span>{{ note.group.name }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tags -->
                    <Card v-if="note.tags.length" variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Tag class="h-4 w-4" />
                                Tags
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in note.tags"
                                    :key="tag.id"
                                    :href="`/notes?tag_id=${tag.id}`"
                                >
                                    <Badge :color="tag.color">
                                        {{ tag.name }}
                                    </Badge>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Shares -->
                    <Card v-if="note.shares?.length" variant="outlined">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Share2 class="h-4 w-4" />
                                Shared With
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div
                                v-for="share in note.shares"
                                :key="share.id"
                                class="flex items-center justify-between text-sm"
                            >
                                <div>
                                    <p class="font-medium">{{ share.shared_with_user.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ share.shared_with_user.email }}
                                    </p>
                                </div>
                                <Badge variant="outline" size="sm">
                                    {{ share.permission }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Decrypt Dialog -->
        <Dialog v-model:open="showDecryptDialog" title="Decrypt Note">
            <div class="space-y-4">
                <p class="text-muted-foreground">
                    Enter the encryption code to view this note's content.
                </p>
                <Input
                    v-model="decryptionCode"
                    type="password"
                    placeholder="Encryption code"
                    :error="decryptError"
                    @keyup.enter="handleDecrypt"
                />
            </div>
            <template #footer>
                <Button variant="outline" @click="showDecryptDialog = false">Cancel</Button>
                <Button :disabled="!decryptionCode || decrypting" @click="handleDecrypt">
                    {{ decrypting ? 'Decrypting...' : 'Decrypt' }}
                </Button>
            </template>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal" title="Delete Note">
            <p class="text-muted-foreground">
                Are you sure you want to delete "{{ note.title }}"?
                This action cannot be undone.
            </p>
            <template #footer>
                <Button variant="outline" @click="deleteModal = false">Cancel</Button>
                <Button variant="destructive" @click="handleDelete">Delete</Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
