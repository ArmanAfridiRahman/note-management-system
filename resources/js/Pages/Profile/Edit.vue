<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button, Input, Label } from '@/Components/ui';
import { useToast } from '@/Composables/useToast';
import { User, Lock, Mail, AlertCircle } from 'lucide-vue-next';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const page = usePage();
const user = page.props.auth.user as { name: string; email: string; email_verified_at: string | null };
const toast = useToast();

// Profile form
const profileForm = useForm({
    name: user.name,
    email: user.email,
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile updated successfully.');
        },
        onError: () => {
            toast.error('Failed to update profile.');
        },
    });
};

// Password form
const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.success('Password updated successfully.');
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value?.focus();
            }
            toast.error('Failed to update password.');
        },
    });
};
</script>

<template>
    <Head title="Profile" />

    <AppLayout title="Profile">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Profile Information -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <User class="h-5 w-5 text-primary" />
                        <CardTitle>Profile Information</CardTitle>
                    </div>
                    <CardDescription>
                        Update your account's profile information and email address.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="profileForm.name"
                                type="text"
                                required
                                autocomplete="name"
                                :error="profileForm.errors.name"
                            />
                            <p v-if="profileForm.errors.name" class="text-sm text-destructive">
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="email"
                                    v-model="profileForm.email"
                                    type="email"
                                    required
                                    autocomplete="username"
                                    class="pl-10"
                                    :error="profileForm.errors.email"
                                />
                            </div>
                            <p v-if="profileForm.errors.email" class="text-sm text-destructive">
                                {{ profileForm.errors.email }}
                            </p>
                        </div>

                        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-lg border border-warning/50 bg-warning/10 p-3">
                            <div class="flex items-start gap-2">
                                <AlertCircle class="h-4 w-4 text-warning mt-0.5" />
                                <div class="text-sm">
                                    <p class="text-foreground">Your email address is unverified.</p>
                                    <Link
                                        :href="route('verification.send')"
                                        method="post"
                                        as="button"
                                        class="text-primary underline hover:text-primary/80"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                    <p v-if="status === 'verification-link-sent'" class="mt-2 font-medium text-green-600">
                                        A new verification link has been sent to your email address.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button type="submit" :disabled="profileForm.processing">
                                {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Update Password -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Lock class="h-5 w-5 text-primary" />
                        <CardTitle>Update Password</CardTitle>
                    </div>
                    <CardDescription>
                        Ensure your account is using a long, random password to stay secure.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="current_password">Current Password</Label>
                            <Input
                                id="current_password"
                                ref="currentPasswordInput"
                                v-model="passwordForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                :error="passwordForm.errors.current_password"
                            />
                            <p v-if="passwordForm.errors.current_password" class="text-sm text-destructive">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                ref="passwordInput"
                                v-model="passwordForm.password"
                                type="password"
                                autocomplete="new-password"
                                :error="passwordForm.errors.password"
                            />
                            <p v-if="passwordForm.errors.password" class="text-sm text-destructive">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                :error="passwordForm.errors.password_confirmation"
                            />
                            <p v-if="passwordForm.errors.password_confirmation" class="text-sm text-destructive">
                                {{ passwordForm.errors.password_confirmation }}
                            </p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button type="submit" :disabled="passwordForm.processing">
                                {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
