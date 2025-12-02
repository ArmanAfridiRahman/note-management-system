import { ref, watch, type Ref } from 'vue';

/**
 * Debounce a ref value
 */
export function useDebounce<T>(value: Ref<T>, delay: number = 300): Ref<T> {
    const debouncedValue = ref(value.value) as Ref<T>;
    let timeout: ReturnType<typeof setTimeout> | null = null;

    watch(value, (newValue) => {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            debouncedValue.value = newValue;
        }, delay);
    });

    return debouncedValue;
}

/**
 * Debounce a function
 */
export function useDebounceFn<T extends (...args: unknown[]) => unknown>(
    fn: T,
    delay: number = 300
): (...args: Parameters<T>) => void {
    let timeout: ReturnType<typeof setTimeout> | null = null;

    return function (this: unknown, ...args: Parameters<T>) {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            fn.apply(this, args);
        }, delay);
    };
}
