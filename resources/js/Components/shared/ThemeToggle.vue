<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Button } from '@/Components/ui';
import { Sun, Moon } from 'lucide-vue-next';
import { isDarkMode, toggleDarkMode, initializeTheme } from '@/lib/utils';

const isDark = ref(false);

function toggle() {
    isDark.value = toggleDarkMode();
}

onMounted(() => {
    initializeTheme();
    isDark.value = isDarkMode();
});
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        @click="toggle"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <Sun v-if="isDark" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
    </Button>
</template>
