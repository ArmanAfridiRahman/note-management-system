import { ref, onMounted } from 'vue';

export function useTheme() {
    const isDark = ref(false);

    function toggle() {
        isDark.value = !isDark.value;
        updateTheme();
    }

    function setTheme(dark: boolean) {
        isDark.value = dark;
        updateTheme();
    }

    function updateTheme() {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }

    function initialize() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        isDark.value = savedTheme === 'dark' || (!savedTheme && prefersDark);
        updateTheme();
    }

    onMounted(() => {
        initialize();

        // Listen for system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = (e: MediaQueryListEvent) => {
            if (!localStorage.getItem('theme')) {
                isDark.value = e.matches;
                updateTheme();
            }
        };

        mediaQuery.addEventListener('change', handleChange);
    });

    return {
        isDark,
        toggle,
        setTheme,
    };
}
