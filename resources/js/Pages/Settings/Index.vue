<script setup lang="ts">
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { AppLayout } from '@/Components/layout';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Button, Toggle, Label, Select } from '@/Components/ui';
import { ThemeToggle } from '@/Components/shared';
import { Settings, Palette, Bell, Shield, Download, Trash2 } from 'lucide-vue-next';

const page = usePage();
const saving = ref(false);

const preferences = ref({
    default_note_color: '',
    notes_per_page: '20',
    email_notifications: true,
    auto_save: true,
});

const colorOptions = [
    { value: '', label: 'None' },
    { value: '#ef4444', label: 'Red' },
    { value: '#f97316', label: 'Orange' },
    { value: '#eab308', label: 'Yellow' },
    { value: '#22c55e', label: 'Green' },
    { value: '#3b82f6', label: 'Blue' },
    { value: '#8b5cf6', label: 'Purple' },
];

const savePreferences = async () => {
    saving.value = true;
    try {
        const response = await fetch('/api/user/preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(preferences.value),
        });

        const data = await response.json();
        if (data.success) {
            // Show success notification
        }
    } catch (error) {
        console.error('Failed to save preferences:', error);
    } finally {
        saving.value = false;
    }
};

const exportData = async () => {
    try {
        const response = await fetch('/api/user/export', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'notes-export.json';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    } catch (error) {
        console.error('Failed to export data:', error);
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
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Theme</Label>
                            <p class="text-sm text-muted-foreground">
                                Switch between light and dark mode.
                            </p>
                        </div>
                        <ThemeToggle />
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Default Note Color</Label>
                            <p class="text-sm text-muted-foreground">
                                Color for new notes (optional).
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-for="color in colorOptions"
                                :key="color.value"
                                type="button"
                                class="h-6 w-6 rounded-full border-2 transition-all"
                                :class="[
                                    preferences.default_note_color === color.value
                                        ? 'border-primary scale-110'
                                        : 'border-transparent hover:border-muted-foreground/50'
                                ]"
                                :style="{ backgroundColor: color.value || 'var(--muted)' }"
                                :title="color.label"
                                @click="preferences.default_note_color = color.value"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Notifications -->
            <Card variant="outlined">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Bell class="h-5 w-5" />
                        <CardTitle>Notifications</CardTitle>
                    </div>
                    <CardDescription>
                        Manage your notification preferences.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <Label>Email Notifications</Label>
                            <p class="text-sm text-muted-foreground">
                                Receive email notifications for shared notes.
                            </p>
                        </div>
                        <Toggle v-model="preferences.email_notifications" />
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
                        <Select v-model="preferences.notes_per_page" class="w-24">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </Select>
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
