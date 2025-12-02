<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AuthLayout } from '@/Components/layout';
import { Button } from '@/Components/ui';
import { Mail, Send, LogOut } from 'lucide-vue-next';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head title="Verify Email" />

    <AuthLayout
        title="Verify your email"
        description="We've sent a verification link to your email"
    >
        <div class="text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                <Mail class="h-8 w-8 text-primary" />
            </div>

            <p class="text-sm text-muted-foreground">
                Thanks for signing up! Before getting started, please verify your
                email address by clicking on the link we just emailed to you.
            </p>

            <div
                v-if="verificationLinkSent"
                class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-600 dark:bg-green-900/20 dark:text-green-400"
            >
                A new verification link has been sent to your email address.
            </div>

            <form @submit.prevent="submit" class="mt-6">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                >
                    <Send class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Sending...' : 'Resend verification email' }}
                </Button>
            </form>

            <div class="mt-6 border-t border-border pt-6">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                >
                    <LogOut class="mr-1 h-4 w-4" />
                    Sign out
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>
