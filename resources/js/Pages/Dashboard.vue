<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, Accordion } from '@/Components/ui';
import { NotesSection } from '@/Components/notes';
import { NoteStatesChart, TopOpenedNotesChart } from '@/Components/dashboard';
import { BarChart3, TrendingUp } from 'lucide-vue-next';
import type { TagData, GroupData, UserData } from '@/types/models';

interface NoteStates {
    total: number;
    pinned: number;
    favorites: number;
    encrypted: number;
    archived: number;
    regular: number;
    pinned_favorite: number;
    pinned_encrypted: number;
    favorite_encrypted: number;
    pinned_favorite_encrypted: number;
}

interface TopOpenedNote {
    id: number;
    title: string;
    open_count: number;
    color: string | null;
}

interface Props {
    noteStates: NoteStates;
    topOpenedNotes: TopOpenedNote[];
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

defineProps<Props>();
const page = usePage();

// Create filters that include tags from URL
const pinnedFilters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const filters: Record<string, string> = { filter: 'pinned' };
    if (tagsParam) filters.tags = tagsParam;
    return filters;
});

const favoritesFilters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const filters: Record<string, string> = { filter: 'favorites' };
    if (tagsParam) filters.tags = tagsParam;
    return filters;
});

const regularFilters = computed(() => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    const filters: Record<string, string> = { filter: 'regular' };
    if (tagsParam) filters.tags = tagsParam;
    return filters;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <div class="space-y-2">
            <!-- Charts & Analytics Accordion -->
            <Accordion title="Analytics & Insights" :default-open="true">
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Note States Chart -->
                    <Card variant="outlined" padding="md">
                        <div class="flex items-center gap-2 mb-4">
                            <BarChart3 class="h-4 w-4 text-muted-foreground" />
                            <span class="text-sm font-medium">Note Distribution</span>
                        </div>
                        <NoteStatesChart :note-states="noteStates" />
                    </Card>

                    <!-- Top Opened Notes Chart -->
                    <Card variant="outlined" padding="md">
                        <div class="flex items-center gap-2 mb-4">
                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                            <span class="text-sm font-medium">Top 5 Most Viewed</span>
                        </div>
                        <TopOpenedNotesChart :notes="topOpenedNotes" />
                    </Card>
                </div>
            </Accordion>

            <!-- Pinned Notes Accordion -->
            <Accordion
                title="Pinned Notes"
                :default-open="true"
                :count="noteStates.pinned"
            >
                <NotesSection
                    :key="'pinned-' + page.url"
                    fetch-url="/api/notes"
                    :filters="pinnedFilters"
                    :tags="tags"
                    :groups="groups"
                    :users="users"
                    :show-quick-input="false"
                    :draggable="false"
                    empty-title="No pinned notes"
                    empty-description="Pin important notes to access them quickly."
                />
            </Accordion>

            <!-- Favorite Notes Accordion -->
            <Accordion
                title="Favorite Notes"
                :default-open="false"
                :count="noteStates.favorites"
            >
                <NotesSection
                    :key="'favorites-' + page.url"
                    fetch-url="/api/notes"
                    :filters="favoritesFilters"
                    :tags="tags"
                    :groups="groups"
                    :users="users"
                    :show-quick-input="false"
                    :draggable="false"
                    empty-title="No favorite notes"
                    empty-description="Mark notes as favorites to find them here."
                />
            </Accordion>

            <!-- Regular Notes Accordion -->
            <Accordion
                title="Recent Notes"
                :default-open="false"
                :count="noteStates.regular"
            >
                <NotesSection
                    :key="'regular-' + page.url"
                    fetch-url="/api/notes"
                    :filters="regularFilters"
                    :tags="tags"
                    :groups="groups"
                    :users="users"
                    :show-quick-input="false"
                    :draggable="false"
                    empty-title="No regular notes"
                    empty-description="Create your first note to get started."
                />
            </Accordion>
        </div>
    </AppLayout>
</template>
