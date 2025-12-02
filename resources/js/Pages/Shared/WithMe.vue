<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardContent, Badge, Button } from '@/Components/ui';
import { formatRelativeTime } from '@/lib/utils';
import { Share2, Eye, Edit, Clock, User } from 'lucide-vue-next';

interface SharedNote {
    id: number;
    note: {
        id: number;
        title: string;
        slug: string;
        excerpt?: string;
        is_encrypted: boolean;
        updated_at: string;
    };
    owner: {
        id: number;
        name: string;
        email: string;
    };
    permission: 'view' | 'edit';
    expires_at?: string;
    created_at: string;
}

const sharedNotes = ref<SharedNote[]>([]);
const loading = ref(true);

const fetchSharedNotes = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/shared/with-me');
        const data = await response.json();
        if (data.success) {
            sharedNotes.value = data.data;
        }
    } catch (error) {
        console.error('Failed to fetch shared notes:', error);
    } finally {
        loading.value = false;
    }
};

const viewNote = (share: SharedNote) => {
    router.visit(`/notes/${share.note.id}`);
};

onMounted(() => {
    fetchSharedNotes();
});
</script>

<template>
    <Head title="Shared With Me" />

    <AppLayout title="Shared With Me">
        <div class="max-w-4xl">
            <!-- Header -->
            <div class="mb-6">
                <p class="text-muted-foreground">
                    Notes that others have shared with you.
                </p>
            </div>

            <!-- Shared Notes List -->
            <div v-if="loading" class="space-y-4">
                <Card v-for="i in 3" :key="i" variant="outlined" class="animate-pulse">
                    <CardContent class="p-4">
                        <div class="h-5 w-2/3 bg-muted rounded mb-2" />
                        <div class="h-4 w-1/3 bg-muted rounded" />
                    </CardContent>
                </Card>
            </div>

            <div v-else-if="sharedNotes.length === 0" class="text-center py-12">
                <Share2 class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <p class="text-lg font-medium mb-2">No shared notes</p>
                <p class="text-muted-foreground">
                    When someone shares a note with you, it will appear here.
                </p>
            </div>

            <div v-else class="space-y-4">
                <Card
                    v-for="share in sharedNotes"
                    :key="share.id"
                    variant="outlined"
                    hoverable
                    class="cursor-pointer"
                    @click="viewNote(share)"
                >
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium truncate">{{ share.note.title }}</h3>
                                <p v-if="share.note.excerpt" class="mt-1 text-sm text-muted-foreground line-clamp-2">
                                    {{ share.note.is_encrypted ? 'Encrypted content' : share.note.excerpt }}
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <User class="h-3 w-3" />
                                        {{ share.owner.name }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        Shared {{ formatRelativeTime(share.created_at) }}
                                    </span>
                                    <span v-if="share.expires_at" class="flex items-center gap-1 text-yellow-600">
                                        Expires {{ formatRelativeTime(share.expires_at) }}
                                    </span>
                                </div>
                            </div>

                            <Badge :variant="share.permission === 'edit' ? 'default' : 'secondary'">
                                <Edit v-if="share.permission === 'edit'" class="mr-1 h-3 w-3" />
                                <Eye v-else class="mr-1 h-3 w-3" />
                                {{ share.permission }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
