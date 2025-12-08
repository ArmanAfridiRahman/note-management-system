<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { NotesSection } from '@/Components/notes';
import { Archive } from 'lucide-vue-next';
import type { TagData, GroupData, UserData } from '@/types/models';

interface Props {
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
const page = usePage();

// Get tag filters from URL combined with archived filter
const filters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const baseFilters: Record<string, any> = { is_archived: true };
    if (tagsParam) {
        baseFilters.tags = tagsParam;
    }
    return baseFilters;
});
</script>

<template>
    <Head title="Archived Notes" />

    <AppLayout title="Archived Notes">
        <!-- Archive Info Banner -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0 mb-6">
            <div class="flex items-center gap-3 rounded-lg border border-yellow-500/20 bg-yellow-500/5 p-4">
                <Archive class="h-5 w-5 text-yellow-600 flex-shrink-0" />
                <div>
                    <p class="font-medium text-yellow-600 dark:text-yellow-400">Archived Notes</p>
                    <p class="text-sm text-muted-foreground">
                        These notes have been archived. Click on a note to unarchive it and move it back to active notes.
                    </p>
                </div>
            </div>
        </div>

        <NotesSection
            :key="page.url"
            fetch-url="/api/notes"
            :filters="filters"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="true"
            :default-note-values="{ is_archived: true }"
            empty-title="No archived notes"
            empty-description="Notes you archive will appear here."
        />
    </AppLayout>
</template>
