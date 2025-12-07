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
    <Head title="Encrypted Notes" />

    <AppLayout title="Encrypted Notes">
        <NotesSection
            fetch-url="/api/notes"
            :filters="{ filter: 'encrypted' }"
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
