import { ref } from 'vue';

export interface ToastOptions {
    type?: 'info' | 'success' | 'warning' | 'error';
    title?: string;
    message: string;
    duration?: number;
    dismissible?: boolean;
}

export interface Toast {
    id: number;
    type: 'info' | 'success' | 'warning' | 'error';
    title?: string;
    message: string;
    duration: number;
    dismissible: boolean;
}

const toasts = ref<Toast[]>([]);
let nextId = 1;

export function useToast() {
    function addToast(options: ToastOptions) {
        const id = nextId++;
        const toast: Toast = {
            id,
            type: options.type || 'info',
            title: options.title,
            message: options.message,
            duration: options.duration ?? 5000,
            dismissible: options.dismissible ?? true,
        };

        toasts.value.push(toast);

        if (toast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, toast.duration);
        }

        return id;
    }

    function removeToast(id: number) {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function success(message: string, title?: string) {
        return addToast({ type: 'success', message, title });
    }

    function error(message: string, title?: string) {
        return addToast({ type: 'error', message, title });
    }

    function warning(message: string, title?: string) {
        return addToast({ type: 'warning', message, title });
    }

    function info(message: string, title?: string) {
        return addToast({ type: 'info', message, title });
    }

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
    };
}
