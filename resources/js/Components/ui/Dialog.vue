<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    close: [];
}>();

const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    full: 'max-w-4xl',
};

const dialogClasses = computed(() =>
    cn(
        'relative z-10 w-full rounded-lg bg-background p-6 shadow-xl',
        sizeClasses[props.size],
        props.class
    )
);

function close() {
    emit('update:open', false);
    emit('close');
}

function handleEscape(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) {
        close();
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Dialog Content -->
                <div :class="dialogClasses" role="dialog" aria-modal="true">
                    <!-- Close Button -->
                    <button
                        class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        @click="close"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        <span class="sr-only">Close</span>
                    </button>

                    <!-- Header -->
                    <div v-if="title || description" class="mb-4">
                        <h2
                            v-if="title"
                            class="text-lg font-semibold leading-none tracking-tight"
                        >
                            {{ title }}
                        </h2>
                        <p
                            v-if="description"
                            class="mt-1.5 text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <!-- Content -->
                    <slot />

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="mt-6 flex justify-end gap-2">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dialog-enter-active,
.dialog-leave-active {
    transition: opacity 0.2s ease;
}

.dialog-enter-from,
.dialog-leave-to {
    opacity: 0;
}

.dialog-enter-active > div:last-child,
.dialog-leave-active > div:last-child {
    transition: transform 0.2s ease;
}

.dialog-enter-from > div:last-child,
.dialog-leave-to > div:last-child {
    transform: scale(0.95);
}
</style>
