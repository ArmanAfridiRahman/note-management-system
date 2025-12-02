<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardContent, Badge, Button } from '@/Components/ui';
import { NoteCard } from '@/Components/notes';
import { FileText, Archive, Lock, Tag, Folder, Plus, ArrowRight } from 'lucide-vue-next';

interface TagData {
    id: number;
    name: string;
    slug: string;
    color: string;
    notes_count: number;
}

interface GroupData {
    id: number;
    name: string;
    slug: string;
    color?: string;
}

interface NoteData {
    id: number;
    title: string;
    slug: string;
    excerpt?: string;
    is_encrypted: boolean;
    is_pinned: boolean;
    is_archived: boolean;
    is_favorited: boolean;
    color?: string;
    created_at: string;
    updated_at: string;
    group?: GroupData;
    tags: TagData[];
}

interface Stats {
    total_notes: number;
    archived_notes: number;
    encrypted_notes: number;
    total_tags: number;
    total_groups: number;
}

interface Props {
    recentNotes: NoteData[];
    pinnedNotes: NoteData[];
    stats: Stats;
    popularTags: TagData[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5 mb-8">
            <Card variant="outlined">
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <FileText class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.total_notes }}</p>
                            <p class="text-sm text-muted-foreground">Total Notes</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card variant="outlined">
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/20">
                            <Archive class="h-5 w-5 text-yellow-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.archived_notes }}</p>
                            <p class="text-sm text-muted-foreground">Archived</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card variant="outlined">
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                            <Lock class="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.encrypted_notes }}</p>
                            <p class="text-sm text-muted-foreground">Encrypted</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card variant="outlined">
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                            <Tag class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.total_tags }}</p>
                            <p class="text-sm text-muted-foreground">Tags</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card variant="outlined">
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                            <Folder class="h-5 w-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.total_groups }}</p>
                            <p class="text-sm text-muted-foreground">Groups</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Recent Notes -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Recent Notes</h2>
                    <Link href="/notes" class="text-sm text-primary hover:underline flex items-center gap-1">
                        View all <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div v-if="recentNotes.length" class="space-y-3">
                    <NoteCard
                        v-for="note in recentNotes"
                        :key="note.id"
                        :note="note"
                        compact
                    />
                </div>

                <Card v-else variant="flat" class="text-center py-8">
                    <p class="text-muted-foreground mb-4">No notes yet</p>
                    <Link href="/notes/create">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Create your first note
                        </Button>
                    </Link>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pinned Notes -->
                <Card variant="outlined" padding="none">
                    <CardHeader>
                        <CardTitle class="text-base">Pinned Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="pinnedNotes.length" class="space-y-2">
                            <Link
                                v-for="note in pinnedNotes"
                                :key="note.id"
                                :href="`/notes/${note.id}`"
                                class="block p-2 rounded-md hover:bg-muted transition-colors"
                            >
                                <p class="font-medium truncate">{{ note.title }}</p>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">No pinned notes</p>
                    </CardContent>
                </Card>

                <!-- Popular Tags -->
                <Card variant="outlined" padding="none">
                    <CardHeader>
                        <CardTitle class="text-base">Popular Tags</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="popularTags.length" class="flex flex-wrap gap-2">
                            <Link
                                v-for="tag in popularTags"
                                :key="tag.id"
                                :href="`/notes?tag_id=${tag.id}`"
                            >
                                <Badge :color="tag.color">
                                    {{ tag.name }}
                                    <span class="ml-1 opacity-70">{{ tag.notes_count }}</span>
                                </Badge>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">No tags yet</p>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card variant="outlined" padding="none">
                    <CardHeader>
                        <CardTitle class="text-base">Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link href="/notes/create" class="block">
                            <Button variant="outline" class="w-full justify-start">
                                <Plus class="mr-2 h-4 w-4" />
                                New Note
                            </Button>
                        </Link>
                        <Link href="/tags" class="block">
                            <Button variant="ghost" class="w-full justify-start">
                                <Tag class="mr-2 h-4 w-4" />
                                Manage Tags
                            </Button>
                        </Link>
                        <Link href="/groups" class="block">
                            <Button variant="ghost" class="w-full justify-start">
                                <Folder class="mr-2 h-4 w-4" />
                                Manage Groups
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
