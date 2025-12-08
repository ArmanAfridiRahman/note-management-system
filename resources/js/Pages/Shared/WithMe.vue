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
    <Head title="Shared With Me" />

    <AppLayout title="Shared With Me">
        <!-- Info Banner -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0 mb-6">
            <div class="flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 p-4">
                <Share2 class="h-5 w-5 text-primary flex-shrink-0" />
                <div>
                    <p class="font-medium text-primary">Shared With Me</p>
                    <p class="text-sm text-muted-foreground">
                        Notes that others have shared with you. Click on a note to view or edit based on your permission level.
                    </p>
                </div>
            </div>
        </div>

        <NotesSection
            fetch-url="/api/shared/with-me/notes"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="false"
            :draggable="false"
            empty-title="No shared notes"
            empty-description="When someone shares a note with you, it will appear here."
        />
    </AppLayout>
</template>
