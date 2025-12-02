<script setup lang="ts">
import { Button } from '@/Components/ui';
import { cn } from '@/lib/utils';

interface Props {
    icon?: string;
    title: string;
    description?: string;
    actionLabel?: string;
    class?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    action: [];
}>();
</script>

<template>
    <div
        :class="cn(
            'flex flex-col items-center justify-center py-16 text-center',
            props.class
        )"
    >
        <span v-if="icon" class="mb-4 text-6xl opacity-30">{{ icon }}</span>
        <h3 class="mb-2 text-lg font-medium text-foreground">{{ title }}</h3>
        <p v-if="description" class="mb-6 max-w-md text-muted-foreground">
            {{ description }}
        </p>
        <Button v-if="actionLabel" @click="emit('action')">
            {{ actionLabel }}
        </Button>
    </div>
</template>
