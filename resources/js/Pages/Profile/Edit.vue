<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button, Input, Label } from '@/Components/ui';
import { useToast } from '@/Composables/useToast';
import { User, Lock, Mail, AlertCircle, Camera, Palette, Upload } from 'lucide-vue-next';
import axios from 'axios';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

interface UserWithProfile {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    avatar?: string;
    avatar_url: string;
    color?: string;
    display_color: string;
    initials: string;
}

const page = usePage();
const user = page.props.auth.user as UserWithProfile;
const toast = useToast();

// Avatar state
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string>(user.avatar_url);
const uploadingAvatar = ref(false);

// Color state
const selectedColor = ref(user.color || '');
const savingColors = ref(false);

// Default color palette
const colorOptions = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#64748b',
];

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

// Avatar upload
const triggerAvatarUpload = () => {
    avatarInput.value?.click();
};

const handleAvatarChange = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        toast.error('Please select an image file.');
        return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        toast.error('Image size must be less than 2MB.');
        return;
    }

    // Preview using FileReader
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    // Upload
    uploadingAvatar.value = true;
    try {
        const formData = new FormData();
        formData.append('avatar', file);

        const response = await axios.post('/api/user/avatar', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            toast.success('Avatar uploaded successfully.');
            window.location.reload();
        }
    } catch (err: any) {
        console.error('Failed to upload avatar:', err);
        const errorMessage = err.response?.data?.message || 'Failed to upload avatar.';
        toast.error(errorMessage);
        avatarPreview.value = user.avatar_url;
    } finally {
        uploadingAvatar.value = false;
    }
};

const removeAvatar = async () => {
    uploadingAvatar.value = true;
    try {
        const response = await axios.delete('/api/user/avatar');

        if (response.data.success) {
            toast.success('Avatar removed.');
            window.location.reload();
        }
    } catch (err) {
        console.error('Failed to remove avatar:', err);
        toast.error('Failed to remove avatar.');
    } finally {
        uploadingAvatar.value = false;
    }
};

// Color update
const saveColor = async () => {
    savingColors.value = true;
    try {
        const response = await axios.put('/api/user/colors', {
            color: selectedColor.value || null,
        });

        if (response.data.success) {
            toast.success('Theme color updated.');
            window.location.reload();
        }
    } catch (err) {
        console.error('Failed to update color:', err);
        toast.error('Failed to update color.');
    } finally {
        savingColors.value = false;
    }
};

// Check if user has custom avatar
const hasCustomAvatar = computed(() => !!user.avatar);
</script>

<template>
    <Head title="Profile" />

    <AppLayout title="Profile">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Avatar & Theme Color -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Palette class="h-5 w-5 text-primary" />
                        <CardTitle>Appearance</CardTitle>
                    </div>
                    <CardDescription>
                        Customize your avatar and theme color.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <!-- Avatar Section -->
                        <div class="flex items-center gap-6">
                            <!-- Avatar Preview -->
                            <div class="relative">
                                <img
                                    :src="avatarPreview"
                                    :alt="user.name"
                                    class="w-20 h-20 rounded-full object-cover"
                                />
                                <button
                                    type="button"
                                    class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-md hover:bg-primary/90 transition-colors"
                                    :disabled="uploadingAvatar"
                                    @click="triggerAvatarUpload"
                                >
                                    <Camera class="w-4 h-4" />
                                </button>
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="hidden"
                                    @change="handleAvatarChange"
                                />
                            </div>

                            <!-- Avatar Actions -->
                            <div class="flex-1">
                                <h4 class="font-medium text-foreground mb-1">Profile Picture</h4>
                                <p class="text-sm text-muted-foreground mb-3">
                                    Upload a new avatar. Max 2MB, JPG/PNG/GIF/WebP.
                                </p>
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="uploadingAvatar"
                                        @click="triggerAvatarUpload"
                                    >
                                        <Upload class="w-4 h-4 mr-2" />
                                        {{ uploadingAvatar ? 'Uploading...' : 'Upload' }}
                                    </Button>
                                    <Button
                                        v-if="hasCustomAvatar"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        :disabled="uploadingAvatar"
                                        @click="removeAvatar"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Theme Color Section -->
                        <div class="space-y-4 pt-4 border-t border-border">
                            <div>
                                <Label class="text-sm mb-2 block">Theme Color</Label>
                                <p class="text-xs text-muted-foreground mb-3">
                                    Choose a color for buttons, links, and your avatar background.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="color in colorOptions"
                                        :key="color"
                                        type="button"
                                        class="h-8 w-8 rounded-full border-2 transition-all"
                                        :class="selectedColor === color ? 'ring-2 ring-offset-2 ring-foreground' : 'hover:scale-110 border-transparent'"
                                        :style="{ backgroundColor: color }"
                                        @click="selectedColor = color"
                                    />
                                    <button
                                        type="button"
                                        class="h-8 w-8 rounded-full border-2 border-dashed border-muted-foreground/30 flex items-center justify-center text-xs text-muted-foreground hover:border-muted-foreground/50 transition-all"
                                        :class="!selectedColor ? 'ring-2 ring-offset-2 ring-foreground' : ''"
                                        @click="selectedColor = ''"
                                        title="Default (Red)"
                                    >
                                        <span class="sr-only">Default</span>
                                        <span aria-hidden="true">-</span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <Button
                                    type="button"
                                    :disabled="savingColors"
                                    @click="saveColor"
                                >
                                    {{ savingColors ? 'Saving...' : 'Save Color' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

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
