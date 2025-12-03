<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardContent, Badge, Button } from '@/Components/ui';
import { NotesSection } from '@/Components/notes';
import { FileText, Archive, Lock, Tag, Folder, Star, Plus, ArrowRight } from 'lucide-vue-next';
import type { TagData, GroupData, UserData } from '@/types/models';

interface Stats {
    total_notes: number;
    archived_notes: number;
    encrypted_notes: number;
    favorited_notes: number;
    total_tags: number;
    total_groups: number;
}

interface Props {
    stats: Stats;
    popularTags: TagData[];
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <!-- Stats Cards -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0 mb-8">
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
                <Link href="/notes">
                    <Card variant="outlined" class="hover:border-primary/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                    <FileText class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.total_notes }}</p>
                                    <p class="text-xs text-muted-foreground">Total Notes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/notes/favorites">
                    <Card variant="outlined" class="hover:border-amber-500/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/20">
                                    <Star class="h-5 w-5 text-amber-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.favorited_notes }}</p>
                                    <p class="text-xs text-muted-foreground">Favorites</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/notes/encrypted">
                    <Card variant="outlined" class="hover:border-green-500/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                                    <Lock class="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.encrypted_notes }}</p>
                                    <p class="text-xs text-muted-foreground">Encrypted</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/notes/archived">
                    <Card variant="outlined" class="hover:border-yellow-500/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/20">
                                    <Archive class="h-5 w-5 text-yellow-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.archived_notes }}</p>
                                    <p class="text-xs text-muted-foreground">Archived</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/tags">
                    <Card variant="outlined" class="hover:border-blue-500/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                    <Tag class="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.total_tags }}</p>
                                    <p class="text-xs text-muted-foreground">Tags</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Link href="/groups">
                    <Card variant="outlined" class="hover:border-purple-500/50 transition-colors cursor-pointer">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                                    <Folder class="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold">{{ stats.total_groups }}</p>
                                    <p class="text-xs text-muted-foreground">Groups</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>

        <!-- Notes Section (without quick input) -->
        <NotesSection
            fetch-url="/api/notes"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="false"
            empty-title="No notes yet"
            empty-description="Create your first note to get started."
        />
    </AppLayout>
</template>
