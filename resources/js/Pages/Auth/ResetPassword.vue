<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button, Input, Label } from '@/Components/ui';
import { Mail, Lock, KeyRound } from 'lucide-vue-next';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <AuthLayout
        title="Reset your password"
        description="Enter your new password below"
    >
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <Label for="email">Email address</Label>
                <div class="relative mt-1.5">
                    <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        :error="form.errors.email"
                        class="pl-10"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
            </div>

            <div>
                <Label for="password">New password</Label>
                <div class="relative mt-1.5">
                    <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        placeholder="Enter new password"
                        :error="form.errors.password"
                        class="pl-10"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <div>
                <Label for="password_confirmation">Confirm new password</Label>
                <div class="relative mt-1.5">
                    <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm new password"
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
                <KeyRound class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Resetting...' : 'Reset password' }}
            </Button>
        </form>
    </AuthLayout>
</template>
