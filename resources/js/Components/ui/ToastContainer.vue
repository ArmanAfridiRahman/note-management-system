<script setup lang="ts">
import { useToast } from '@/Composables/useToast';
import Toast from './Toast.vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm">
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <Toast
                    v-for="toast in toasts"
                    :key="toast.id"
                    :type="toast.type"
                    :title="toast.title"
                    :message="toast.message"
                    :dismissible="toast.dismissible"
                    @dismiss="removeToast(toast.id)"
                />
            </TransitionGroup>
        </div>
    </Teleport>
</template>
