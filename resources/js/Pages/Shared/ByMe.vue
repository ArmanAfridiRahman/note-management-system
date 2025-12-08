<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { NotesSection } from '@/Components/notes';
import { Share2 } from 'lucide-vue-next';
import type { TagData, GroupData, UserData } from '@/types/models';

interface Props {
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Shared By Me" />

    <AppLayout title="Shared By Me">
        <!-- Info Banner -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0 mb-6">
            <div class="flex items-center gap-3 rounded-lg border border-blue-500/20 bg-blue-500/5 p-4">
                <Share2 class="h-5 w-5 text-blue-600 flex-shrink-0" />
                <div>
                    <p class="font-medium text-blue-600 dark:text-blue-400">Shared By Me</p>
                    <p class="text-sm text-muted-foreground">
                        Notes you have shared with others. You can manage sharing settings from the note's share menu.
                    </p>
                </div>
            </div>
        </div>

        <NotesSection
            fetch-url="/api/shared/by-me/notes"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="false"
            :draggable="false"
            empty-title="No shared notes"
            empty-description="When you share a note with someone, it will appear here."
        />
    </AppLayout>
</template>
