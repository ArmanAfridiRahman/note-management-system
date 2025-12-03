/**
 * Application Configuration
 *
 * These settings can be adjusted to modify app behavior
 */

export const appConfig = {
    /**
     * Auto-save interval in milliseconds for notes
     * Default: 15000 (15 seconds)
     * Set to 0 to disable auto-save
     */
    autoSaveInterval: 15000,

    /**
     * Debounce delay for search inputs in milliseconds
     */
    searchDebounceDelay: 300,

    /**
     * Number of items to load per page for infinite scroll
     */
    itemsPerPage: 20,

    /**
     * Maximum number of tags to display on a note card
     */
    maxTagsDisplay: 3,

    /**
     * Local storage keys
     */
    storage: {
        draftNote: 'note-draft',
        theme: 'theme',
    },
};

export default appConfig;
