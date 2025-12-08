<script setup lang="ts">
import { computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { cn } from '@/lib/utils';

interface Props {
    modelValue?: Date | string | null;
    placeholder?: string;
    minDate?: Date | string;
    maxDate?: Date | string;
    disabled?: boolean;
    clearable?: boolean;
    enableTime?: boolean;
    timeOnly?: boolean;
    format?: string;
    previewFormat?: string;
    error?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select date and time',
    clearable: true,
    enableTime: true,
    timeOnly: false,
    format: 'yyyy-MM-dd HH:mm',
    previewFormat: 'MMM dd, yyyy HH:mm',
});

const emit = defineEmits<{
    'update:modelValue': [value: Date | string | null];
}>();

const internalValue = computed({
    get: () => props.modelValue ?? null,
    set: (value) => emit('update:modelValue', value),
});

// Check if dark mode is active
const isDark = computed(() => {
    if (typeof document !== 'undefined') {
        return document.documentElement.classList.contains('dark');
    }
    return false;
});
</script>

<template>
    <div class="relative">
        <VueDatePicker
            v-model="internalValue"
            :placeholder="placeholder"
            :min-date="minDate"
            :max-date="maxDate"
            :disabled="disabled"
            :clearable="clearable"
            :enable-time-picker="enableTime"
            :time-picker="timeOnly"
            :format="format"
            :preview-format="previewFormat"
            :dark="isDark"
            auto-apply
            :teleport="true"
            :input-class-name="cn(
                'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm transition-colors',
                'outline-none ring-0 focus:outline-none focus:ring-0',
                'focus:border-primary/50',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'placeholder:text-muted-foreground',
                error ? 'border-destructive' : 'border-input',
                props.class
            )"
            menu-class-name="dp-custom-menu"
            calendar-cell-class-name="dp-custom-cell"
        />
        <p v-if="error" class="mt-1.5 text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>

<style>
/* Custom styling for the datepicker to match our theme */
.dp__theme_dark {
    --dp-background-color: #1f1f1f;
    --dp-text-color: #fff;
    --dp-hover-color: #2d2d2d;
    --dp-hover-text-color: #fff;
    --dp-hover-icon-color: #fff;
    --dp-primary-color: #e94560;
    --dp-primary-disabled-color: rgba(233, 69, 96, 0.5);
    --dp-primary-text-color: #fff;
    --dp-secondary-color: #2d2d2d;
    --dp-border-color: #3d3d3d;
    --dp-menu-border-color: #3d3d3d;
    --dp-border-color-hover: rgba(233, 69, 96, 0.5);
    --dp-disabled-color: #2d2d2d;
    --dp-disabled-color-text: #6b7280;
    --dp-scroll-bar-background: #2d2d2d;
    --dp-scroll-bar-color: #e94560;
    --dp-success-color: #e94560;
    --dp-success-color-disabled: rgba(233, 69, 96, 0.5);
    --dp-icon-color: #9ca3af;
    --dp-danger-color: #ef4444;
    --dp-marker-color: #e94560;
    --dp-tooltip-color: #1f1f1f;
    --dp-highlight-color: rgba(233, 69, 96, 0.1);
    --dp-range-between-dates-background-color: rgba(233, 69, 96, 0.1);
    --dp-range-between-dates-text-color: #fff;
    --dp-range-between-border-color: rgba(233, 69, 96, 0.1);
}

.dp__theme_light {
    --dp-background-color: #ffffff;
    --dp-text-color: #1f1f1f;
    --dp-hover-color: #f3f4f6;
    --dp-hover-text-color: #1f1f1f;
    --dp-hover-icon-color: #1f1f1f;
    --dp-primary-color: #e94560;
    --dp-primary-disabled-color: rgba(233, 69, 96, 0.5);
    --dp-primary-text-color: #fff;
    --dp-secondary-color: #f3f4f6;
    --dp-border-color: #e5e7eb;
    --dp-menu-border-color: #e5e7eb;
    --dp-border-color-hover: rgba(233, 69, 96, 0.5);
    --dp-disabled-color: #f3f4f6;
    --dp-disabled-color-text: #9ca3af;
    --dp-scroll-bar-background: #f3f4f6;
    --dp-scroll-bar-color: #e94560;
    --dp-success-color: #e94560;
    --dp-success-color-disabled: rgba(233, 69, 96, 0.5);
    --dp-icon-color: #6b7280;
    --dp-danger-color: #ef4444;
    --dp-marker-color: #e94560;
    --dp-tooltip-color: #ffffff;
    --dp-highlight-color: rgba(233, 69, 96, 0.1);
    --dp-range-between-dates-background-color: rgba(233, 69, 96, 0.1);
    --dp-range-between-dates-text-color: #1f1f1f;
    --dp-range-between-border-color: rgba(233, 69, 96, 0.1);
}

/* Menu/Popup styling - ensure solid background */
.dp__menu {
    background-color: var(--dp-background-color) !important;
    z-index: 9999 !important;
}

.dp__menu_inner {
    background-color: var(--dp-background-color) !important;
}

.dp__calendar {
    background-color: var(--dp-background-color) !important;
}

.dp__calendar_header {
    background-color: var(--dp-background-color) !important;
}

.dp__calendar_row {
    background-color: var(--dp-background-color) !important;
}

.dp__action_row {
    background-color: var(--dp-background-color) !important;
}

.dp__time_display {
    background-color: var(--dp-background-color) !important;
}

.dp__overlay {
    background-color: var(--dp-background-color) !important;
}

.dp__overlay_container {
    background-color: var(--dp-background-color) !important;
}

.dp__time_picker_overlay_container {
    background-color: var(--dp-background-color) !important;
}

.dp__button {
    background-color: var(--dp-background-color) !important;
}

.dp__month_year_wrap {
    background-color: var(--dp-background-color) !important;
}

.dp-custom-menu {
    border-radius: 0.5rem !important;
    box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.3), 0 8px 10px -6px rgb(0 0 0 / 0.2) !important;
    background-color: var(--dp-background-color) !important;
    overflow: hidden;
}

/* Selected cell styling */
.dp__active_date {
    background-color: #e94560 !important;
    color: #fff !important;
}

.dp__today {
    border-color: #e94560 !important;
}

/* Time picker styling */
.dp__time_display {
    color: var(--dp-text-color);
}

.dp__inc_dec_button {
    background-color: var(--dp-background-color) !important;
}

.dp__inc_dec_button:hover {
    background-color: var(--dp-hover-color) !important;
}

.dp__time_col {
    background-color: var(--dp-background-color) !important;
}

/* Action buttons */
.dp__action_button {
    border-radius: 0.375rem;
    font-weight: 500;
}

.dp__action_select {
    background-color: #e94560 !important;
    color: #fff !important;
}

.dp__action_cancel {
    border-color: var(--dp-border-color) !important;
    color: var(--dp-text-color) !important;
    background-color: var(--dp-background-color) !important;
}

.dp__action_cancel:hover {
    background-color: var(--dp-hover-color) !important;
}

/* Input icon */
.dp__input_icon {
    color: var(--dp-icon-color);
}

.dp__clear_icon {
    color: var(--dp-icon-color);
}

.dp__clear_icon:hover {
    color: var(--dp-text-color);
}

/* Arrow/navigation buttons */
.dp__inner_nav {
    background-color: var(--dp-background-color) !important;
}

.dp__inner_nav:hover {
    background-color: var(--dp-hover-color) !important;
}

/* Month/Year selector */
.dp__month_year_select {
    background-color: var(--dp-background-color) !important;
}

.dp__month_year_select:hover {
    background-color: var(--dp-hover-color) !important;
}

/* Instance calendar container */
.dp__instance_calendar {
    background-color: var(--dp-background-color) !important;
}
</style>
