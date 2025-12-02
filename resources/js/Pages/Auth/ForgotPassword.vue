<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button, Input, Label } from '@/Components/ui';
import { Mail, Send, ArrowLeft } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <AuthLayout
        title="Forgot your password?"
        description="Enter your email and we'll send you a reset link"
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

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
            >
                <Send class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Sending...' : 'Send reset link' }}
            </Button>
        </form>

        <template #footer>
            <Link href="/login" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="mr-1 h-4 w-4" />
                Back to sign in
            </Link>
        </template>
    </AuthLayout>
</template>
