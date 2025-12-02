<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button, Input, Label } from '@/Components/ui';
import { Lock, Shield } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Confirm Password" />

    <AuthLayout
        title="Secure area"
        description="Please confirm your password to continue"
    >
        <div class="text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                <Shield class="h-8 w-8 text-yellow-600" />
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <Label for="password">Password</Label>
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
                        autofocus
                    />
                </div>
            </div>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Confirming...' : 'Confirm password' }}
            </Button>
        </form>
    </AuthLayout>
</template>
