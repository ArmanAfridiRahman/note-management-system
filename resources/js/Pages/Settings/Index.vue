<script setup lang="ts">
import { ref } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button, Toggle, Label, Input, Dialog } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import { useToast } from '@/Composables/useToast';
import { Palette, Settings, Trash2, AlertTriangle } from 'lucide-vue-next';

const page = usePage();
const { success, error } = useToast();
const saving = ref(false);

const preferences = ref({
    notes_per_page: 20,
    auto_save: true,
});

const savePreferences = async () => {
    saving.value = true;
    try {
        const { data } = await axios.put('/api/user/preferences', preferences.value);
        if (data.success) {
            success('Preferences saved successfully');
        } else {
            error('Failed to save preferences');
        }
    } catch (err) {
        console.error('Failed to save preferences:', err);
        error('Failed to save preferences');
    } finally {
        saving.value = false;
    }
};

// Delete account
const deleteModal = ref(false);
const deletePassword = ref('');
const deleteLoading = ref(false);

const confirmDelete = async () => {
    deleteLoading.value = true;
    try {
        await axios.delete(route('profile.destroy'), {
            data: { password: deletePassword.value },
        });
        router.visit('/');
    } catch (err: any) {
        error(err.response?.data?.message || 'Failed to delete account');
    } finally {
        deleteLoading.value = false;
    }
};
</script>

<template>
    <Head title="Settings" />

    <AppLayout title="Settings">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Appearance -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Palette class="h-5 w-5 text-primary" />
                        <CardTitle>Appearance</CardTitle>
                    </div>
                    <CardDescription>
                        Customize how the app looks and feels.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Theme</Label>
                            <p class="text-sm text-muted-foreground">
                                Switch between light and dark mode.
                            </p>
                        </div>
                        <ThemeToggle />
                    </div>
                </CardContent>
            </Card>

            <!-- Editor -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Settings class="h-5 w-5 text-primary" />
                        <CardTitle>Editor</CardTitle>
                    </div>
                    <CardDescription>
                        Configure the note editor behavior.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Auto-save</Label>
                            <p class="text-sm text-muted-foreground">
                                Automatically save notes as you type.
                            </p>
                        </div>
                        <Toggle v-model="preferences.auto_save" />
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Notes per page</Label>
                            <p class="text-sm text-muted-foreground">
                                Number of notes to load at once.
                            </p>
                        </div>
                        <Input
                            v-model="preferences.notes_per_page"
                            type="number"
                            min="5"
                            max="100"
                            class="w-20 text-center"
                        />
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button :disabled="saving" @click="savePreferences">
                            {{ saving ? 'Saving...' : 'Save Preferences' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Danger Zone -->
            <Card variant="outlined" class="border-destructive/50">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Trash2 class="h-5 w-5 text-destructive" />
                        <CardTitle class="text-destructive">Danger Zone</CardTitle>
                    </div>
                    <CardDescription>
                        Irreversible and destructive actions.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <Label class="text-destructive">Delete Account</Label>
                            <p class="text-sm text-muted-foreground">
                                Permanently delete your account and all data.
                            </p>
                        </div>
                        <Button variant="destructive" @click="deleteModal = true">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete Account
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Account Modal -->
        <Dialog v-model:open="deleteModal" title="Delete Account">
            <div class="space-y-4">
                <div class="flex items-start gap-3 p-3 rounded-lg bg-destructive/10 border border-destructive/20">
                    <AlertTriangle class="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
                    <div class="text-sm">
                        <p class="font-medium text-destructive">This action cannot be undone</p>
                        <p class="text-muted-foreground mt-1">
                            This will permanently delete your account and all associated data including notes, tags, and groups.
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="delete-password">Enter your password to confirm</Label>
                    <Input
                        id="delete-password"
                        v-model="deletePassword"
                        type="password"
                        placeholder="Your password"
                    />
                </div>
            </div>
            <template #footer>
                <Button variant="outline" @click="deleteModal = false" :disabled="deleteLoading">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmDelete" :disabled="!deletePassword || deleteLoading">
                    {{ deleteLoading ? 'Deleting...' : 'Delete My Account' }}
                </Button>
            </template>
        </Dialog>
    </AppLayout>
</template>
