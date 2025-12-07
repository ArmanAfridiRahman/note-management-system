<script setup lang="ts">
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button, Toggle, Label, Input } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import { useToast } from '@/Composables/useToast';
import { Settings, Palette, Shield, Download, Trash2 } from 'lucide-vue-next';

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

const exportData = async () => {
    try {
        const response = await axios.get('/api/user/export', {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'notes-export.json';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    } catch (err) {
        console.error('Failed to export data:', err);
        error('Failed to export data');
    }
};
</script>

<template>
    <Head title="Settings" />

    <AppLayout title="Settings">
        <div class="max-w-3xl space-y-6">
            <!-- Appearance -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Palette class="h-5 w-5" />
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
                        <Settings class="h-5 w-5" />
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
                </CardContent>
            </Card>

            <!-- Data -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Shield class="h-5 w-5" />
                        <CardTitle>Data & Privacy</CardTitle>
                    </div>
                    <CardDescription>
                        Manage your data and privacy settings.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Export Data</Label>
                            <p class="text-sm text-muted-foreground">
                                Download all your notes and data.
                            </p>
                        </div>
                        <Button variant="outline" @click="exportData">
                            <Download class="mr-2 h-4 w-4" />
                            Export
                        </Button>
                    </div>

                    <div class="flex items-center justify-between border-t pt-4">
                        <div>
                            <Label class="text-destructive">Delete Account</Label>
                            <p class="text-sm text-muted-foreground">
                                Permanently delete your account and all data.
                            </p>
                        </div>
                        <Button variant="destructive">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Save Button -->
            <div class="flex justify-end">
                <Button :disabled="saving" @click="savePreferences">
                    {{ saving ? 'Saving...' : 'Save Preferences' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
