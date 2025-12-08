<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { Search, ChevronDown, ChevronRight, Loader2, X, Hash } from 'lucide-vue-next';
import type { TagData } from '@/types/models';

const page = usePage();

// State
const isExpanded = ref(true);
const searchQuery = ref('');
const tags = ref<TagData[]>([]);
const isLoading = ref(false);
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

// Parse selected tags from URL reactively
const selectedTags = computed(() => {
    const url = page.url;
    const searchParams = new URLSearchParams(url.split('?')[1] || '');
    const tagsParam = searchParams.get('tags');
    if (tagsParam) {
        return tagsParam.split(',').map(Number).filter(n => !isNaN(n));
    }
    return [];
});

// Computed
const displayedTags = computed(() => {
    if (searchQuery.value) {
        return tags.value;
    }
    return tags.value.slice(0, 8);
});

const remainingCount = computed(() => {
    if (searchQuery.value) return 0;
    return Math.max(0, tags.value.length - 8);
});

// Methods
async function fetchTags() {
    if (isLoading.value) return;

    isLoading.value = true;

    try {
        const params: Record<string, any> = {
            order: 'popular',
        };

        if (searchQuery.value) {
            params.q = searchQuery.value;
        }

        const response = await axios.get('/api/tags', { params });

        if (response.data.success) {
            tags.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to fetch tags:', error);
    } finally {
        isLoading.value = false;
    }
}

function handleSearch() {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }

    searchTimeout.value = setTimeout(() => {
        fetchTags();
    }, 300);
}

function toggleTag(tagId: number) {
    const currentSelection = selectedTags.value;
    const newSelection = currentSelection.includes(tagId)
        ? currentSelection.filter(id => id !== tagId)
        : [...currentSelection, tagId];

    navigateWithTags(newSelection);
}

function clearAllTags() {
    navigateWithTags([]);
}

function navigateWithTags(tagIds: number[]) {
    const currentPath = window.location.pathname;
    const url = new URL(window.location.href);

    if (tagIds.length > 0) {
        url.searchParams.set('tags', tagIds.join(','));
    } else {
        url.searchParams.delete('tags');
    }

    router.get(currentPath + url.search, {}, {
        preserveState: false,
        preserveScroll: true,
    });
}

function isSelected(tagId: number): boolean {
    return selectedTags.value.includes(tagId);
}

function getTagById(tagId: number): TagData | undefined {
    return tags.value.find(t => t.id === tagId);
}

// Watchers
watch(searchQuery, handleSearch);

// Lifecycle
onMounted(() => {
    fetchTags();
});
</script>

<template>
    <div class="mt-8">
        <!-- Header -->
        <button
            type="button"
            class="w-full flex items-center justify-between px-3 mb-2"
            @click="isExpanded = !isExpanded"
        >
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Filter by Tags
            </h3>
            <component
                :is="isExpanded ? ChevronDown : ChevronRight"
                class="h-3.5 w-3.5 text-muted-foreground"
            />
        </button>

        <!-- Expanded Content -->
        <div v-show="isExpanded" class="space-y-3">
            <!-- Search Input -->
            <div class="px-3">
                <div class="relative">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search tags..."
                        class="w-full h-8 pl-8 pr-3 text-sm rounded-md border border-input bg-background outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors"
                    />
                </div>
            </div>

            <!-- Active Filters -->
            <div v-if="selectedTags.length > 0" class="px-3 pb-3 border-b border-border/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] text-muted-foreground uppercase tracking-wider">Active</span>
                    <button
                        type="button"
                        class="text-[10px] text-muted-foreground hover:text-destructive transition-colors"
                        @click="clearAllTags"
                    >
                        Clear all
                    </button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tagId in selectedTags"
                        :key="tagId"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium border-2 border-primary bg-primary/10 text-foreground transition-colors hover:bg-primary/20"
                        @click="toggleTag(tagId)"
                    >
                        {{ getTagById(tagId)?.name || 'Tag' }}
                        <X class="h-3 w-3 text-muted-foreground" />
                    </button>
                </div>
            </div>

            <!-- Tags List -->
            <div class="space-y-0.5">
                <!-- Loading State -->
                <div v-if="isLoading && tags.length === 0" class="px-3 py-3 text-center">
                    <Loader2 class="h-4 w-4 animate-spin mx-auto text-muted-foreground" />
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="tags.length === 0"
                    class="px-3 py-3 text-center text-xs text-muted-foreground"
                >
                    {{ searchQuery ? 'No tags found' : 'No tags yet' }}
                </div>

                <!-- Tag Items -->
                <template v-else>
                    <button
                        v-for="tag in displayedTags"
                        :key="tag.id"
                        type="button"
                        :class="cn(
                            'w-full flex items-center gap-2.5 rounded-md px-3 py-1.5 text-sm transition-all border-2',
                            isSelected(tag.id)
                                ? 'border-primary bg-primary/10 text-foreground font-medium'
                                : 'border-transparent text-muted-foreground hover:bg-muted hover:text-foreground'
                        )"
                        @click="toggleTag(tag.id)"
                    >
                        <span class="truncate flex-1 text-left">{{ tag.name }}</span>
                        <span
                            v-if="tag.notes_count !== undefined && tag.notes_count > 0"
                            :class="cn(
                                'text-[10px] px-1.5 py-0.5 rounded-full',
                                isSelected(tag.id)
                                    ? 'bg-primary/20 text-primary'
                                    : 'bg-muted text-muted-foreground'
                            )"
                        >
                            {{ tag.notes_count }}
                        </span>
                    </button>

                    <!-- Show More -->
                    <button
                        v-if="remainingCount > 0"
                        type="button"
                        class="w-full flex items-center gap-2.5 rounded-md px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                        @click="fetchTags"
                    >
                        <Hash class="h-3.5 w-3.5" />
                        <span>{{ remainingCount }} more tags...</span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
