<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Button, ToastContainer } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import Sidebar from './Sidebar.vue';
import {
    Menu,
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
                <!-- Left Section: Menu + Title -->
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

                    <!-- Page Title (optional) -->
                    <h1 v-if="title" class="text-lg font-semibold text-foreground hidden sm:block">
                        {{ title }}
                    </h1>
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
                <slot />
            </main>
        </div>

        <!-- Toast Notifications -->
        <ToastContainer />
    </div>
</template>
