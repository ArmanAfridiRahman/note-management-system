<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { NotesSection } from '@/Components/notes';
import type { TagData, GroupData, UserData } from '@/types/models';

interface Props {
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
const page = usePage();

// Get tag filters from URL combined with pinned filter
const filters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const baseFilters: Record<string, string> = { filter: 'pinned' };
    if (tagsParam) {
        baseFilters.tags = tagsParam;
    }
    return baseFilters;
});
</script>

<template>
    <Head title="Pinned Notes" />

    <AppLayout title="Pinned Notes">
        <NotesSection
            :key="page.url"
            fetch-url="/api/notes"
            :filters="filters"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="true"
            :default-note-values="{ is_pinned: true }"
            empty-title="No pinned notes"
            empty-description="Pin important notes for quick access. They will appear here."
        />
    </AppLayout>
</template>
