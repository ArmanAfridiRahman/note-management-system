import { watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface AuthUser {
    color?: string;
    secondary_color?: string;
}

/**
 * Converts a hex color to HSL values
 */
function hexToHsl(hex: string): { h: number; s: number; l: number } | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (!result) return null;

    let r = parseInt(result[1], 16) / 255;
    let g = parseInt(result[2], 16) / 255;
    let b = parseInt(result[3], 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r:
                h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
                break;
            case g:
                h = ((b - r) / d + 2) / 6;
                break;
            case b:
                h = ((r - g) / d + 4) / 6;
                break;
        }
    }

    return {
        h: Math.round(h * 360),
        s: Math.round(s * 100),
        l: Math.round(l * 100),
    };
}

/**
 * Generates color variations for the theme
 */
function generateColorVariations(hex: string): Record<string, string> {
    const hsl = hexToHsl(hex);
    if (!hsl) return {};

    return {
        '--color-primary': hex,
        '--color-accent': hex,
        '--color-ring': hex,
        // Chart colors - variations of the primary
        '--color-chart-1': hex,
        '--color-chart-2': `hsl(${hsl.h}, ${hsl.s}%, ${Math.min(hsl.l + 10, 90)}%)`,
        '--color-chart-3': `hsl(${hsl.h}, ${hsl.s}%, ${Math.min(hsl.l + 20, 90)}%)`,
        '--color-chart-4': `hsl(${hsl.h}, ${hsl.s}%, ${Math.min(hsl.l + 30, 90)}%)`,
        '--color-chart-5': `hsl(${hsl.h}, ${hsl.s}%, ${Math.min(hsl.l + 40, 95)}%)`,
    };
}

/**
 * Applies user's color preference to the document
 */
function applyUserTheme(color?: string) {
    const root = document.documentElement;

    if (!color) {
        // Remove custom properties to use defaults
        const props = [
            '--color-primary',
            '--color-accent',
            '--color-ring',
            '--color-chart-1',
            '--color-chart-2',
            '--color-chart-3',
            '--color-chart-4',
            '--color-chart-5',
        ];
        props.forEach(prop => root.style.removeProperty(prop));
        return;
    }

    const variations = generateColorVariations(color);
    Object.entries(variations).forEach(([prop, value]) => {
        root.style.setProperty(prop, value);
    });
}

/**
 * Composable to apply user's theme color preference
 */
export function useUserTheme() {
    const page = usePage();

    const applyTheme = () => {
        const user = page.props.auth?.user as AuthUser | undefined;
        applyUserTheme(user?.color ?? undefined);
    };

    onMounted(() => {
        applyTheme();
    });

    // Watch for user changes (e.g., after login or profile update)
    watch(
        () => (page.props.auth?.user as AuthUser | undefined)?.color,
        () => {
            applyTheme();
        }
    );

    return { applyTheme };
}
