import { ref, onMounted, onUnmounted, watch, type Ref, toRef, computed } from 'vue';
import axios from 'axios';

interface UseInfiniteScrollOptions<T> {
    url: string;
    initialItems?: T[];
    filters?: Record<string, unknown>;
    perPage?: number;
}

export function useInfiniteScroll<T>(options: UseInfiniteScrollOptions<T>) {
    const { url, initialItems = [], perPage = 20 } = options;

    const items = ref<T[]>([...initialItems]) as Ref<T[]>;
    const loading = ref(false);
    const initialLoading = ref(initialItems.length === 0); // Start as true if no initial items
    const hasMore = ref(true); // Always start as true to allow initial load
    const cursor = ref<string | null>(null);
    const sentinel = ref<HTMLElement | null>(null);
    const error = ref<string | null>(null);
    const currentFilters = ref<Record<string, unknown>>(options.filters || {});

    let observer: IntersectionObserver | null = null;
    const isInitialized = initialItems.length > 0;

    async function loadMore() {
        if (loading.value || !hasMore.value) return;

        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(url, {
                params: {
                    ...currentFilters.value,
                    cursor: cursor.value,
                    per_page: perPage,
                },
            });

            const data = response.data;
            items.value.push(...data.data);
            cursor.value = data.meta?.next_cursor || null;
            hasMore.value = data.meta?.has_more ?? false;
        } catch (err) {
            console.error('Failed to load more items:', err);
            error.value = 'Failed to load items. Please try again.';
        } finally {
            loading.value = false;
            initialLoading.value = false;
        }
    }

    async function refresh(newFilters?: Record<string, unknown>) {
        if (newFilters) {
            currentFilters.value = newFilters;
        }
        items.value = [];
        cursor.value = null;
        hasMore.value = true;
        initialLoading.value = true;
        await loadMore();
    }

    function updateFilters(newFilters: Record<string, unknown>) {
        currentFilters.value = newFilters;
        refresh();
    }

    function setupObserver() {
        if (observer) {
            observer.disconnect();
        }

        observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && !loading.value && hasMore.value) {
                    loadMore();
                }
            },
            { rootMargin: '100px' }
        );

        if (sentinel.value) {
            observer.observe(sentinel.value);
        }
    }

    onMounted(() => {
        setupObserver();
        // Only load if we don't have initial items
        if (!isInitialized) {
            loadMore();
        }
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
    });

    // Re-setup observer when sentinel changes
    watch(sentinel, () => {
        setupObserver();
    });

    return {
        items,
        loading,
        initialLoading,
        hasMore,
        error,
        sentinel,
        loadMore,
        refresh,
        updateFilters,
    };
}
