<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    align?: 'left' | 'right';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    align: 'left',
});

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

function toggle() {
    isOpen.value = !isOpen.value;
}

function close() {
    isOpen.value = false;
}

function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        close();
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block">
        <!-- Trigger -->
        <div @click="toggle">
            <slot name="trigger" />
        </div>

        <!-- Menu -->
        <Transition name="dropdown">
            <div
                v-if="isOpen"
                :class="cn(
                    'absolute z-50 mt-2 min-w-[180px] rounded-md border border-border bg-background py-1 shadow-lg',
                    align === 'right' ? 'right-0' : 'left-0',
                    props.class
                )"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
