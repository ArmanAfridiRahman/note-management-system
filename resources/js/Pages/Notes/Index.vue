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

const props = defineProps<Props>();
const page = usePage();

// Get tag filters from URL (reactive via page.url)
const filters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    if (tagsParam) {
        return { tags: tagsParam };
    }
    return {};
});

// Page title based on active filters
const pageTitle = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    if (tagsParam) {
        const tagIds = tagsParam.split(',').map(Number);
        const tagNames = tagIds
            .map(id => props.tags.find(t => t.id === id)?.name)
            .filter(Boolean);
        if (tagNames.length > 0) {
            return `Notes: ${tagNames.join(', ')}`;
        }
    }
    return 'All Notes';
});
</script>

<template>
    <Head title="Notes" />

    <AppLayout :title="pageTitle">
        <NotesSection
            :key="page.url"
            fetch-url="/api/notes"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="true"
            :filters="filters"
            empty-title="No notes found"
            empty-description="Create your first note by clicking the input above."
        />
    </AppLayout>
</template>
