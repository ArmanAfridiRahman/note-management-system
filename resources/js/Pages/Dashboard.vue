<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardContent, Accordion } from '@/Components/ui';
import {
    NoteStatesChart,
    TopOpenedNotesChart,
    NoteInfoModal,
    DashboardNoteCard
} from '@/Components/dashboard';
import { BarChart3, TrendingUp } from 'lucide-vue-next';
import type { NoteData, TagData, GroupData, UserData } from '@/types/models';

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
    pinnedNotes: NoteData[];
    favoriteNotes: NoteData[];
    regularNotes: NoteData[];
    tags: TagData[];
    groups: GroupData[];
    users?: UserData[];
}

const props = defineProps<Props>();

const selectedNote = ref<NoteData | null>(null);
const showInfoModal = ref(false);

function openNoteInfo(note: NoteData) {
    selectedNote.value = note;
    showInfoModal.value = true;
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <div class="max-w-5xl mx-auto space-y-2">
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
                :count="pinnedNotes.length"
            >
                <div v-if="pinnedNotes.length === 0" class="text-center py-8 text-sm text-muted-foreground">
                    No pinned notes yet. Pin important notes to access them quickly.
                </div>
                <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <DashboardNoteCard
                        v-for="note in pinnedNotes"
                        :key="note.id"
                        :note="note"
                        @show-info="openNoteInfo"
                    />
                </div>
            </Accordion>

            <!-- Favorite Notes Accordion -->
            <Accordion
                title="Favorite Notes"
                :default-open="false"
                :count="favoriteNotes.length"
            >
                <div v-if="favoriteNotes.length === 0" class="text-center py-8 text-sm text-muted-foreground">
                    No favorite notes yet. Mark notes as favorites to find them here.
                </div>
                <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <DashboardNoteCard
                        v-for="note in favoriteNotes"
                        :key="note.id"
                        :note="note"
                        @show-info="openNoteInfo"
                    />
                </div>
            </Accordion>

            <!-- Regular Notes Accordion -->
            <Accordion
                title="Recent Notes"
                :default-open="false"
                :count="regularNotes.length"
            >
                <div v-if="regularNotes.length === 0" class="text-center py-8 text-sm text-muted-foreground">
                    No regular notes yet. Create your first note to get started.
                </div>
                <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <DashboardNoteCard
                        v-for="note in regularNotes"
                        :key="note.id"
                        :note="note"
                        @show-info="openNoteInfo"
                    />
                </div>
                <div v-if="regularNotes.length >= 20" class="mt-4 text-center">
                    <Link
                        href="/notes"
                        class="text-sm text-primary hover:underline"
                    >
                        View all notes
                    </Link>
                </div>
            </Accordion>
        </div>

        <!-- Note Info Modal -->
        <NoteInfoModal
            v-model:open="showInfoModal"
            :note="selectedNote"
        />
    </AppLayout>
</template>
