import { ref, watch, onUnmounted, type Ref } from 'vue';
import { appConfig } from '@/config/app';

interface UseAutoSaveOptions<T> {
    data: Ref<T>;
    storageKey: string;
    onSave?: (data: T) => Promise<void>;
    interval?: number;
    enabled?: Ref<boolean>;
}

export function useAutoSave<T extends Record<string, unknown>>(options: UseAutoSaveOptions<T>) {
    const {
        data,
        storageKey,
        onSave,
        interval = appConfig.autoSaveInterval,
        enabled = ref(true),
    } = options;

    const isDirty = ref(false);
    const isSaving = ref(false);
    const lastSaved = ref<Date | null>(null);
    const error = ref<string | null>(null);

    let saveTimer: ReturnType<typeof setInterval> | null = null;
    let initialData: string = '';

    // Save to local storage
    function saveToLocal() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(data.value));
        } catch (e) {
            console.error('Failed to save to local storage:', e);
        }
    }

    // Load from local storage
    function loadFromLocal(): T | null {
        try {
            const stored = localStorage.getItem(storageKey);
            if (stored) {
                return JSON.parse(stored) as T;
            }
        } catch (e) {
            console.error('Failed to load from local storage:', e);
        }
        return null;
    }

    // Clear local storage
    function clearLocal() {
        try {
            localStorage.removeItem(storageKey);
        } catch (e) {
            console.error('Failed to clear local storage:', e);
        }
    }

    // Save to server
    async function saveToServer() {
        if (!onSave || !isDirty.value || isSaving.value || !enabled.value) return;

        isSaving.value = true;
        error.value = null;

        try {
            await onSave(data.value);
            lastSaved.value = new Date();
            isDirty.value = false;
            // Clear local storage after successful save
            clearLocal();
        } catch (e) {
            error.value = 'Failed to save. Changes are stored locally.';
            console.error('Auto-save failed:', e);
        } finally {
            isSaving.value = false;
        }
    }

    // Start auto-save timer
    function startAutoSave() {
        if (saveTimer) {
            clearInterval(saveTimer);
        }

        if (interval > 0) {
            saveTimer = setInterval(() => {
                if (isDirty.value && enabled.value) {
                    saveToServer();
                }
            }, interval);
        }
    }

    // Stop auto-save timer
    function stopAutoSave() {
        if (saveTimer) {
            clearInterval(saveTimer);
            saveTimer = null;
        }
    }

    // Manual save
    async function save() {
        await saveToServer();
    }

    // Mark as dirty and save to local
    function markDirty() {
        isDirty.value = true;
        saveToLocal();
    }

    // Reset state
    function reset() {
        isDirty.value = false;
        error.value = null;
        clearLocal();
        initialData = JSON.stringify(data.value);
    }

    // Check if data has changed from initial
    function hasChanges(): boolean {
        return JSON.stringify(data.value) !== initialData;
    }

    // Watch for data changes
    watch(
        data,
        () => {
            if (hasChanges()) {
                markDirty();
            }
        },
        { deep: true }
    );

    // Watch enabled state
    watch(enabled, (isEnabled) => {
        if (isEnabled) {
            startAutoSave();
        } else {
            stopAutoSave();
        }
    });

    // Initialize
    function init(loadDraft: boolean = true) {
        initialData = JSON.stringify(data.value);

        if (loadDraft) {
            const draft = loadFromLocal();
            if (draft) {
                Object.assign(data.value, draft);
                isDirty.value = true;
            }
        }

        if (enabled.value) {
            startAutoSave();
        }
    }

    // Cleanup on unmount
    onUnmounted(() => {
        stopAutoSave();
    });

    return {
        isDirty,
        isSaving,
        lastSaved,
        error,
        save,
        reset,
        init,
        loadFromLocal,
        clearLocal,
        hasChanges,
        startAutoSave,
        stopAutoSave,
    };
}
