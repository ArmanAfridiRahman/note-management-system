<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import { FileText, Lock, Share2, Tag, Folder, Search, ArrowRight, Check, LayoutDashboard, Code2, Database, Layers, Palette, Zap, Shield } from 'lucide-vue-next';

defineProps<{
    canLogin: boolean;
    canRegister: boolean;
}>();

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);

const features = [
    {
        icon: FileText,
        title: 'Organized Notes',
        description: 'Create, edit, and organize your notes with a clean, intuitive interface.',
    },
    {
        icon: Lock,
        title: 'Private & Encrypted',
        description: 'Protect sensitive notes with AES-256 encryption and unique access codes.',
    },
    {
        icon: Tag,
        title: 'Tags & Groups',
        description: 'Organize notes with colorful tags and hierarchical groups.',
    },
    {
        icon: Share2,
        title: 'Secure Sharing',
        description: 'Share notes with others using view or edit permissions.',
    },
    {
        icon: Search,
        title: 'Instant Search',
        description: 'Find any note instantly with powerful full-text search.',
    },
    {
        icon: Folder,
        title: 'Smart Organization',
        description: 'Pin, favorite, and archive notes to stay organized.',
    },
];

const benefits = [
    'Unlimited notes and tags',
    'End-to-end encryption available',
    'Dark and light mode',
    'Mobile-friendly design',
    'Fast and responsive',
    'No ads, ever',
];

const techStack = [
    {
        name: 'Laravel',
        description: 'PHP backend framework powering the API and authentication',
        icon: Code2,
        color: 'text-red-500',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
    },
    {
        name: 'Vue 3',
        description: 'Modern reactive frontend with Composition API',
        icon: Layers,
        color: 'text-emerald-500',
        bgColor: 'bg-emerald-100 dark:bg-emerald-900/30',
    },
    {
        name: 'Inertia.js',
        description: 'Seamless SPA experience without building an API',
        icon: Zap,
        color: 'text-purple-500',
        bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
    {
        name: 'TypeScript',
        description: 'Type-safe code for better reliability',
        icon: Code2,
        color: 'text-blue-500',
        bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
        name: 'Tailwind CSS',
        description: 'Utility-first styling for rapid UI development',
        icon: Palette,
        color: 'text-cyan-500',
        bgColor: 'bg-cyan-100 dark:bg-cyan-900/30',
    },
    {
        name: 'MySQL',
        description: 'Reliable database for persistent storage',
        icon: Database,
        color: 'text-orange-500',
        bgColor: 'bg-orange-100 dark:bg-orange-900/30',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <FileText class="h-4 w-4 text-primary-foreground" />
                    </div>
                    <span class="text-xl font-bold">NoteVault</span>
                </div>

                <nav class="flex items-center gap-4">
                    <ThemeToggle />
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard">
                            <Button class="gap-2">
                                <LayoutDashboard class="h-4 w-4" />
                                Dashboard
                            </Button>
                        </Link>
                    </template>
                    <template v-else-if="canLogin">
                        <Link href="/login">
                            <Button variant="ghost">Sign in</Button>
                        </Link>
                        <Link v-if="canRegister" href="/register">
                            <Button>Get Started</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 text-center">
            <div class="mx-auto max-w-3xl">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
                    Your thoughts,
                    <span class="text-primary">secured</span>
                </h1>
                <p class="mt-6 text-lg text-muted-foreground md:text-xl">
                    A modern note-taking app with powerful organization, encryption, and sharing features.
                    Keep your ideas safe and accessible.
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard">
                            <Button size="lg" class="gap-2">
                                Go to Dashboard
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link v-if="canRegister" href="/register">
                            <Button size="lg" class="gap-2">
                                Start for free
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="outline" size="lg">
                                Sign in to your account
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Preview Mock -->
            <div class="mt-16 rounded-xl border border-border bg-muted/30 p-2 shadow-2xl">
                <div class="rounded-lg border border-border bg-background p-4">
                    <div class="flex items-center gap-2 border-b border-border pb-4">
                        <div class="h-3 w-3 rounded-full bg-red-500" />
                        <div class="h-3 w-3 rounded-full bg-yellow-500" />
                        <div class="h-3 w-3 rounded-full bg-green-500" />
                        <span class="ml-4 text-sm text-muted-foreground">NoteVault Dashboard</span>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-border p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-accent" />
                                <div class="h-4 w-24 rounded bg-muted" />
                            </div>
                            <div class="space-y-2">
                                <div class="h-3 w-full rounded bg-muted" />
                                <div class="h-3 w-3/4 rounded bg-muted" />
                            </div>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <Lock class="h-4 w-4 text-yellow-500" />
                                <div class="h-4 w-20 rounded bg-muted" />
                            </div>
                            <div class="space-y-2">
                                <div class="h-3 w-full rounded bg-muted" />
                                <div class="h-3 w-2/3 rounded bg-muted" />
                            </div>
                        </div>
                        <div class="rounded-lg border border-border p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-green-500" />
                                <div class="h-4 w-28 rounded bg-muted" />
                            </div>
                            <div class="space-y-2">
                                <div class="h-3 w-full rounded bg-muted" />
                                <div class="h-3 w-4/5 rounded bg-muted" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="border-t border-border bg-muted/30 py-20">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <h2 class="text-3xl font-bold">Everything you need</h2>
                    <p class="mt-4 text-muted-foreground">
                        Powerful features to help you capture, organize, and protect your notes.
                    </p>
                </div>

                <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-xl border border-border bg-background p-6 transition-shadow hover:shadow-lg"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <component :is="feature.icon" class="h-6 w-6 text-primary" />
                        </div>
                        <h3 class="mt-4 text-lg font-semibold">{{ feature.title }}</h3>
                        <p class="mt-2 text-muted-foreground">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="py-20">
            <div class="container mx-auto px-4">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <h2 class="text-3xl font-bold">About NoteVault</h2>
                        <p class="mt-4 text-muted-foreground">
                            NoteVault is a modern, full-featured note-taking application designed for individuals who value both productivity and privacy.
                        </p>
                        <div class="mt-6 space-y-4 text-muted-foreground">
                            <p>
                                Built as a showcase of modern web development practices, NoteVault combines the power of Laravel's robust backend with Vue 3's reactive frontend to deliver a seamless single-page application experience.
                            </p>
                            <p>
                                Whether you're jotting down quick thoughts, organizing complex projects, or securing sensitive information with military-grade encryption, NoteVault provides the tools you need to manage your digital notes effectively.
                            </p>
                            <p>
                                The application features real-time search, drag-and-drop organization, collaborative sharing with granular permissions, and a beautiful interface that adapts to your preferred theme.
                            </p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border bg-muted/30 p-8">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg border border-border bg-background p-4 text-center">
                                <div class="flex h-10 w-10 mx-auto items-center justify-center rounded-lg bg-primary/10">
                                    <FileText class="h-5 w-5 text-primary" />
                                </div>
                                <p class="mt-2 text-2xl font-bold">Rich</p>
                                <p class="text-sm text-muted-foreground">Text Editor</p>
                            </div>
                            <div class="rounded-lg border border-border bg-background p-4 text-center">
                                <div class="flex h-10 w-10 mx-auto items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                                    <Shield class="h-5 w-5 text-yellow-600" />
                                </div>
                                <p class="mt-2 text-2xl font-bold">AES-256</p>
                                <p class="text-sm text-muted-foreground">Encryption</p>
                            </div>
                            <div class="rounded-lg border border-border bg-background p-4 text-center">
                                <div class="flex h-10 w-10 mx-auto items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                    <Share2 class="h-5 w-5 text-green-600" />
                                </div>
                                <p class="mt-2 text-2xl font-bold">Secure</p>
                                <p class="text-sm text-muted-foreground">Sharing</p>
                            </div>
                            <div class="rounded-lg border border-border bg-background p-4 text-center">
                                <div class="flex h-10 w-10 mx-auto items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                    <Search class="h-5 w-5 text-blue-600" />
                                </div>
                                <p class="mt-2 text-2xl font-bold">Full-text</p>
                                <p class="text-sm text-muted-foreground">Search</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech Stack Section -->
        <section class="border-t border-border bg-muted/30 py-20">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <h2 class="text-3xl font-bold">Built with Modern Tech</h2>
                    <p class="mt-4 text-muted-foreground">
                        Powered by industry-leading technologies for performance, security, and developer experience.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="tech in techStack"
                        :key="tech.name"
                        class="flex items-start gap-4 rounded-xl border border-border bg-background p-6 transition-shadow hover:shadow-lg"
                    >
                        <div :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-lg', tech.bgColor]">
                            <component :is="tech.icon" :class="['h-6 w-6', tech.color]" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ tech.name }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">{{ tech.description }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-12 rounded-xl border border-border bg-background p-6">
                    <h3 class="text-lg font-semibold text-center mb-4">Additional Technologies</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Vite</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">shadcn-vue</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Lucide Icons</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Axios</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">TipTap Editor</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Chart.js</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Bcrypt</span>
                        <span class="px-3 py-1 rounded-full bg-muted text-sm">Laravel Sanctum</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="py-20">
            <div class="container mx-auto px-4">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <h2 class="text-3xl font-bold">Why choose NoteVault?</h2>
                        <p class="mt-4 text-muted-foreground">
                            Built with privacy and productivity in mind. Your notes deserve the best.
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li
                                v-for="benefit in benefits"
                                :key="benefit"
                                class="flex items-center gap-3"
                            >
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                    <Check class="h-4 w-4 text-green-600" />
                                </div>
                                <span>{{ benefit }}</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <Link v-if="isLoggedIn" href="/dashboard">
                                <Button size="lg">Go to Dashboard</Button>
                            </Link>
                            <Link v-else-if="canRegister" href="/register">
                                <Button size="lg">Create your account</Button>
                            </Link>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border bg-muted/30 p-8">
                        <div class="space-y-4">
                            <div class="flex items-center gap-4 rounded-lg border border-border bg-background p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                    <FileText class="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-medium">Quick Notes</p>
                                    <p class="text-sm text-muted-foreground">Capture ideas instantly</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 rounded-lg border border-border bg-background p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                                    <Lock class="h-5 w-5 text-yellow-600" />
                                </div>
                                <div>
                                    <p class="font-medium">Encrypted Notes</p>
                                    <p class="text-sm text-muted-foreground">Military-grade encryption</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 rounded-lg border border-border bg-background p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                    <Share2 class="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="font-medium">Shared Notes</p>
                                    <p class="text-sm text-muted-foreground">Collaborate securely</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="border-t border-border bg-primary py-16 text-primary-foreground">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold">{{ isLoggedIn ? 'Welcome back!' : 'Ready to get started?' }}</h2>
                <p class="mt-4 text-primary-foreground/80">
                    {{ isLoggedIn ? 'Continue managing your notes securely.' : 'Join thousands of users who trust NoteVault with their notes.' }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link v-if="isLoggedIn" href="/dashboard">
                        <Button size="lg" variant="secondary" class="gap-2">
                            Go to Dashboard
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link v-else-if="canRegister" href="/register">
                        <Button size="lg" variant="secondary" class="gap-2">
                            Create free account
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border py-8">
            <div class="container mx-auto px-4">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded bg-primary">
                            <FileText class="h-3 w-3 text-primary-foreground" />
                        </div>
                        <span class="font-semibold">NoteVault</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} NoteVault. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
