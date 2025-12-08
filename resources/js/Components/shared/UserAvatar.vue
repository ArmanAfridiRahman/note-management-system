<script setup lang="ts">
import { ref, computed } from 'vue';
import type { UserData } from '@/types/models';

interface Props {
    user: UserData;
    size?: 'xs' | 'sm' | 'md' | 'lg';
    showTooltip?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    showTooltip: true,
});

const showingTooltip = ref(false);

const sizeClasses = {
    xs: 'w-5 h-5',
    sm: 'w-7 h-7',
    md: 'w-9 h-9',
    lg: 'w-12 h-12',
};

// Get avatar URL - prefer avatar_url if available, fallback to generated
const avatarUrl = computed(() => {
    if (props.user.avatar_url) {
        return props.user.avatar_url;
    }
    // Fallback to UI Avatars
    const name = encodeURIComponent(props.user.name);
    const color = props.user.display_color?.replace('#', '') || 'ef4444';
    return `https://ui-avatars.com/api/?name=${name}&background=${color}&color=ffffff&size=128&bold=true`;
});
</script>

<template>
    <div
        class="relative inline-block"
        @mouseenter="showingTooltip = true"
        @mouseleave="showingTooltip = false"
    >
        <img
            :src="avatarUrl"
            :alt="user.name"
            :class="[
                'rounded-full border-2 border-background object-cover',
                sizeClasses[size]
            ]"
        />

        <!-- Tooltip -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="showTooltip && showingTooltip"
                class="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1.5 bg-popover text-popover-foreground text-xs rounded-md shadow-md border border-border whitespace-nowrap"
            >
                <div class="font-medium">{{ user.name }}</div>
                <div class="text-muted-foreground text-[10px]">{{ user.email }}</div>
                <!-- Arrow -->
                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-px">
                    <div class="border-4 border-transparent border-t-border"></div>
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -mt-[1px] border-4 border-transparent border-t-popover"></div>
                </div>
            </div>
        </Transition>
    </div>
</template>
