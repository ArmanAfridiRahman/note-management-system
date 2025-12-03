<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { NotesSection } from '@/Components/notes';
import type { TagData, GroupData, UserData } from '@/types/models';

interface Props {
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
</script>

<template>
    <Head title="Favorite Notes" />

    <AppLayout title="Favorites">
        <NotesSection
            fetch-url="/api/notes"
            :filters="{ filter: 'favorites' }"
            :tags="tags"
            :groups="groups"
            :users="users"
            :show-quick-input="true"
            :default-note-values="{ is_favorited: true }"
            empty-title="No favorite notes"
            empty-description="Notes you mark as favorites will appear here."
        />
    </AppLayout>
</template>
