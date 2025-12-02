<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button, Input, Label } from '@/Components/ui';
import { User, Mail, Lock, UserPlus } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Create Account" />

    <AuthLayout
        title="Create an account"
        description="Start organizing your notes today"
    >
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <Label for="name">Full name</Label>
                <div class="relative mt-1.5">
                    <User class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="name"
                        type="text"
                        v-model="form.name"
                        placeholder="John Doe"
                        :error="form.errors.name"
                        class="pl-10"
                        required
                        autofocus
                        autocomplete="name"
                    />
                </div>
            </div>

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
                        autocomplete="username"
                    />
                </div>
            </div>

            <div>
                <Label for="password">Password</Label>
                <div class="relative mt-1.5">
                    <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        placeholder="Create a password"
                        :error="form.errors.password"
                        class="pl-10"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <div>
                <Label for="password_confirmation">Confirm password</Label>
                <div class="relative mt-1.5">
                    <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm your password"
                        :error="form.errors.password_confirmation"
                        class="pl-10"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
            >
                <UserPlus class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </Button>
        </form>

        <template #footer>
            <p class="text-sm text-muted-foreground">
                Already have an account?
                <Link href="/login" class="font-medium text-primary hover:underline">
                    Sign in
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
