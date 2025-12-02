<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button, Input, Label, Toggle } from '@/Components/ui';
import { Mail, Lock, LogIn } from 'lucide-vue-next';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Sign In" />

    <AuthLayout
        title="Welcome back"
        description="Sign in to your account to continue"
    >
        <div v-if="status" class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-600 dark:bg-green-900/20 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <Label for="email">Email address</Label>
                <div class="relative mt-1.5">
                    <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        placeholder="you@example.com"
                        :error="form.errors.email"
                        class="pl-10"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-primary hover:underline"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative mt-1.5">
                    <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        placeholder="Enter your password"
                        :error="form.errors.password"
                        class="pl-10"
                        required
                        autocomplete="current-password"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Toggle v-model="form.remember" />
                <Label class="cursor-pointer" @click="form.remember = !form.remember">
                    Remember me
                </Label>
            </div>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
            >
                <LogIn class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </Button>
        </form>

        <template #footer>
            <p class="text-sm text-muted-foreground">
                Don't have an account?
                <Link href="/register" class="font-medium text-primary hover:underline">
                    Create one
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
