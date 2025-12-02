<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Button, Input } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import Sidebar from './Sidebar.vue';
import {
    Menu,
    Search,
    Plus,
    Bell,
    User,
    LogOut,
    Settings,
    ChevronDown,
} from 'lucide-vue-next';
import { Dropdown, DropdownItem } from '@/Components/ui';

interface Props {
    title?: string;
}

defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth?.user);

const sidebarOpen = ref(false);
const searchQuery = ref('');

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}
</script>

<template>
    <div class="min-h-screen bg-background">
        <!-- Mobile sidebar backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <Sidebar
            :open="sidebarOpen"
            @close="sidebarOpen = false"
        />

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Top Header -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-border bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/60 lg:px-6">
                <!-- Left Section: Menu + Search -->
                <div class="flex items-center gap-4">
                    <!-- Mobile menu button -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="lg:hidden shrink-0"
                        @click="toggleSidebar"
                    >
                        <Menu class="h-5 w-5" />
                    </Button>

                    <!-- Search Bar -->
                    <div class="w-64 md:w-80 lg:w-96">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search notes..."
                                class="pl-10"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right Section: Actions -->
                <div class="flex items-center gap-2">
                    <!-- Create Note Button -->
                    <Link href="/notes/create">
                        <Button size="sm" class="hidden sm:flex">
                            <Plus class="mr-2 h-4 w-4" />
                            New Note
                        </Button>
                        <Button size="icon" class="sm:hidden">
                            <Plus class="h-4 w-4" />
                        </Button>
                    </Link>

                    <!-- Theme Toggle -->
                    <ThemeToggle />

                    <!-- Notifications -->
                    <Button variant="ghost" size="icon">
                        <Bell class="h-5 w-5" />
                    </Button>

                    <!-- User Menu -->
                    <Dropdown align="right">
                        <template #trigger>
                            <Button variant="ghost" class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <User class="h-4 w-4" />
                                </div>
                                <span class="hidden md:inline">{{ user?.name }}</span>
                                <ChevronDown class="h-4 w-4" />
                            </Button>
                        </template>

                        <template #default="{ close }">
                            <div class="px-4 py-2 border-b border-border">
                                <p class="font-medium">{{ user?.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ user?.email }}</p>
                            </div>
                            <Link href="/settings">
                                <DropdownItem @click="close">
                                    <Settings class="mr-2 h-4 w-4" />
                                    Settings
                                </DropdownItem>
                            </Link>
                            <Link href="/logout" method="post" as="button" class="w-full">
                                <DropdownItem destructive @click="close">
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Logout
                                </DropdownItem>
                            </Link>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                <!-- Page Title -->
                <div v-if="title" class="mb-6">
                    <h1 class="text-2xl font-bold text-foreground">{{ title }}</h1>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
