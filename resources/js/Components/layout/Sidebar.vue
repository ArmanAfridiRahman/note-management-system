<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { cn } from '@/lib/utils';
import { Toggle, Dropdown, DropdownItem } from '@/Components/ui';
import { useToast } from '@/Composables/useToast';
import {
    FileText,
    Home,
    Archive,
    Share2,
    Star,
    Lock,
    Pin,
    X,
    MoreVertical,
    User,
    Settings,
    LogOut,
    Save,
} from 'lucide-vue-next';
import TagsSidebar from './TagsSidebar.vue';

interface Props {
    open: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const toast = useToast();
const currentRoute = computed(() => page.url);
const user = computed(() => page.props.auth?.user as { name: string; email: string } | null);

// Auto-save preference
const autoSave = ref(true);
const autoSaveLoading = ref(false);

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/user/preferences');
        if (data.success && data.data) {
            autoSave.value = data.data.auto_save ?? true;
        }
    } catch (err) {
        console.error('Failed to load preferences:', err);
    }
});

const toggleAutoSave = async () => {
    autoSaveLoading.value = true;
    try {
        const newValue = !autoSave.value;
        const { data } = await axios.put('/api/user/preferences', { auto_save: newValue });
        if (data.success) {
            autoSave.value = newValue;
            toast.success(newValue ? 'Auto-save enabled' : 'Auto-save disabled');
        }
    } catch (err) {
        toast.error('Failed to update preference');
    } finally {
        autoSaveLoading.value = false;
    }
};

interface NavItem {
    name: string;
    href: string;
    icon: typeof Home;
    badge?: number;
}

const mainNavItems: NavItem[] = [
    { name: 'Dashboard', href: '/dashboard', icon: Home },
    { name: 'All Notes', href: '/notes', icon: FileText },
    { name: 'Pinned', href: '/notes/pinned', icon: Pin },
    { name: 'Favorites', href: '/notes/favorites', icon: Star },
    { name: 'Encrypted', href: '/notes/encrypted', icon: Lock },
    { name: 'Archived', href: '/notes/archived', icon: Archive },
];

const sharingItems: NavItem[] = [
    { name: 'Shared with Me', href: '/shared/with-me', icon: Share2 },
    { name: 'My Shares', href: '/shared/by-me', icon: Share2 },
];

function isActive(href: string): boolean {
    const currentPath = currentRoute.value.split('?')[0];
    return currentPath === href;
}

function getUserInitials(name: string): string {
    return name
        .split(' ')
        .map(n => n.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
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

            <!-- Sharing -->
            <div class="mt-6">
                <h3 class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
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

            <!-- Tags Filter -->
            <TagsSidebar />
        </nav>

        <!-- Profile Card -->
        <div class="border-t border-border p-3">
            <!-- Auto-save Toggle -->
            <div class="flex items-center justify-between px-2 py-2 mb-2 rounded-md hover:bg-muted/50 transition-colors">
                <div class="flex items-center gap-2 text-sm">
                    <Save class="h-4 w-4 text-muted-foreground" />
                    <span class="text-muted-foreground">Auto-save</span>
                </div>
                <Toggle
                    :model-value="autoSave"
                    @update:model-value="toggleAutoSave"
                    :disabled="autoSaveLoading"
                    size="sm"
                />
            </div>

            <!-- User Profile -->
            <div v-if="user" class="flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition-colors">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary text-sm font-medium">
                    {{ getUserInitials(user.name) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground truncate">{{ user.name }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                </div>
                <Dropdown align="top-right">
                    <template #trigger>
                        <button class="p-1.5 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">
                            <MoreVertical class="h-4 w-4" />
                        </button>
                    </template>
                    <DropdownItem as="link" href="/profile">
                        <User class="h-4 w-4" />
                        Profile
                    </DropdownItem>
                    <DropdownItem as="link" href="/settings">
                        <Settings class="h-4 w-4" />
                        Settings
                    </DropdownItem>
                    <DropdownItem as="link" href="/logout" method="post" destructive>
                        <LogOut class="h-4 w-4" />
                        Log out
                    </DropdownItem>
                </Dropdown>
            </div>
        </div>
    </aside>
</template>
