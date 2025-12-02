<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ThemeToggle } from '@/Components/shared';
import { FileText } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="absolute top-0 left-0 right-0 z-10">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <FileText class="h-4 w-4 text-primary-foreground" />
                    </div>
                    <span class="text-xl font-bold">NoteVault</span>
                </Link>
                <ThemeToggle />
            </div>
        </header>

        <!-- Content -->
        <div class="flex min-h-screen items-center justify-center px-4 py-20">
            <div class="w-full max-w-md">
                <!-- Card -->
                <div class="rounded-xl border border-border bg-background p-8 shadow-lg">
                    <div class="mb-8 text-center">
                        <h1 class="text-2xl font-bold">{{ title }}</h1>
                        <p v-if="description" class="mt-2 text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>

                <!-- Footer slot for links -->
                <div class="mt-6 text-center">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>
