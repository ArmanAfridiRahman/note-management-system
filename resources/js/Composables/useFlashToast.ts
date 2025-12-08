import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from './useToast';

interface FlashMessages {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export function useFlashToast() {
    const page = usePage();
    const toast = useToast();

    // Watch for flash message changes
    watch(
        () => page.props.flash as FlashMessages | undefined,
        (flash) => {
            if (!flash) return;

            if (flash.success) {
                toast.success(flash.success);
            }
            if (flash.error) {
                toast.error(flash.error);
            }
            if (flash.warning) {
                toast.warning(flash.warning);
            }
            if (flash.info) {
                toast.info(flash.info);
            }
        },
        { immediate: true, deep: true }
    );

    return toast;
}
