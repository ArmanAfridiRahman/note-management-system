<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import {
    FileText,
    Home,
    Archive,
    Tag,
    Folder,
    Share2,
    Star,
    Lock,
    X,
} from 'lucide-vue-next';

interface Props {
    open: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const currentRoute = computed(() => page.url);

interface NavItem {
    name: string;
    href: string;
    icon: typeof Home;
    badge?: number;
}

const mainNavItems: NavItem[] = [
    { name: 'Dashboard', href: '/dashboard', icon: Home },
    { name: 'All Notes', href: '/notes', icon: FileText },
    { name: 'Favorites', href: '/notes?filter=favorites', icon: Star },
    { name: 'Encrypted', href: '/notes?filter=encrypted', icon: Lock },
    { name: 'Archived', href: '/notes/archived', icon: Archive },
];

const organizationItems: NavItem[] = [
    { name: 'Tags', href: '/tags', icon: Tag },
    { name: 'Groups', href: '/groups', icon: Folder },
];

const sharingItems: NavItem[] = [
    { name: 'Shared with Me', href: '/shared/with-me', icon: Share2 },
    { name: 'My Shares', href: '/shared/by-me', icon: Share2 },
];

function isActive(href: string): boolean {
    const currentUrl = currentRoute.value;
    const currentPath = currentUrl.split('?')[0];
    const currentParams = new URLSearchParams(currentUrl.split('?')[1] || '');

    const targetPath = href.split('?')[0];
    const targetParams = new URLSearchParams(href.split('?')[1] || '');

    // Exact match for paths with query params
    if (href.includes('?')) {
        if (currentPath !== targetPath) return false;
        // Check if all target params exist in current params
        for (const [key, value] of targetParams.entries()) {
            if (currentParams.get(key) !== value) return false;
        }
        return true;
    }

    // For paths without query params, ensure no filter is applied
    if (targetPath === '/notes' && !href.includes('?')) {
        return currentPath === '/notes' && !currentParams.has('filter');
    }

    return currentPath === targetPath;
}
</script>

<template>
    <!-- Sidebar -->
    <aside
        :class="cn(
            'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-border bg-background transition-transform duration-300 lg:translate-x-0',
            open ? 'translate-x-0' : '-translate-x-full'
        )"
    >
        <!-- Logo -->
        <div class="flex h-16 items-center justify-between border-b border-border px-4">
            <Link href="/dashboard" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    <FileText class="h-5 w-5" />
                </div>
                <span class="font-semibold text-foreground">Notes</span>
            </Link>
            <button
                class="rounded-md p-1 text-muted-foreground hover:bg-muted lg:hidden"
                @click="emit('close')"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-4">
            <!-- Main Navigation -->
            <div class="space-y-1">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.name"
                    :href="item.href"
                    :class="cn(
                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    )"
                    @click="emit('close')"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.name }}
                    <span
                        v-if="item.badge"
                        class="ml-auto rounded-full bg-accent px-2 py-0.5 text-xs text-accent-foreground"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </div>

            <!-- Organization -->
            <div class="mt-6">
                <h3 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Organization
                </h3>
                <div class="space-y-1">
                    <Link
                        v-for="item in organizationItems"
                        :key="item.name"
                        :href="item.href"
                        :class="cn(
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        )"
                        @click="emit('close')"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.name }}
                    </Link>
                </div>
            </div>

            <!-- Sharing -->
            <div class="mt-6">
                <h3 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Sharing
                </h3>
                <div class="space-y-1">
                    <Link
                        v-for="item in sharingItems"
                        :key="item.name"
                        :href="item.href"
                        :class="cn(
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        )"
                        @click="emit('close')"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.name }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Footer -->
        <div class="border-t border-border p-4">
            <p class="text-xs text-muted-foreground text-center">
                Note Management System
            </p>
        </div>
    </aside>
</template>
