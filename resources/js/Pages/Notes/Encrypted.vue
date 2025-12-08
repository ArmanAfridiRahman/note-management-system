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

// Get tag filters from URL combined with encrypted filter
const filters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const baseFilters: Record<string, string> = { filter: 'encrypted' };
    if (tagsParam) {
        baseFilters.tags = tagsParam;
    }
    return baseFilters;
});
</script>

<template>
    <Head title="Encrypted Notes" />

    <AppLayout title="Encrypted Notes">
        <NotesSection
            :key="page.url"
            fetch-url="/api/notes"
            :filters="filters"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="true"
            :default-note-values="{ is_encrypted: true }"
            empty-title="No encrypted notes"
            empty-description="Encrypted notes will appear here. Create one to secure your private content."
        />
    </AppLayout>
</template>
