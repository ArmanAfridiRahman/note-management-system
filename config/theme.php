<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Theme Configuration
    |--------------------------------------------------------------------------
    |
    | This file controls the visual theme of the application including colors,
    | fonts, and other styling options. The frontend will read these values
    | and apply them through CSS variables.
    |
    | IMPORTANT: Only 3 main colors are used (no gradients)
    | - Primary: Main brand color for headers, primary actions
    | - Secondary: Supporting color for secondary elements
    | - Accent: Highlight color for CTAs, important actions
    |
    */

    'colors' => [
        // Light mode colors
        'light' => [
            'primary' => '#1a1a2e',           // Dark navy - headers, primary buttons
            'primary_foreground' => '#ffffff', // White text on primary
            'secondary' => '#16213e',          // Darker navy - secondary elements
            'secondary_foreground' => '#ffffff',
            'accent' => '#e94560',             // Coral red - CTAs, highlights
            'accent_foreground' => '#ffffff',
            'background' => '#ffffff',         // Page background
            'foreground' => '#1a1a2e',         // Main text color
            'muted' => '#f5f5f5',              // Muted backgrounds
            'muted_foreground' => '#6b7280',   // Muted text
            'border' => '#e5e7eb',             // Border color
            'input' => '#e5e7eb',              // Input border
            'ring' => '#1a1a2e',               // Focus ring
            'destructive' => '#dc2626',        // Destructive actions
            'destructive_foreground' => '#ffffff',
        ],

        // Dark mode colors
        'dark' => [
            'primary' => '#e94560',            // Coral red as primary in dark
            'primary_foreground' => '#ffffff',
            'secondary' => '#1a1a2e',          // Dark navy
            'secondary_foreground' => '#f5f5f5',
            'accent' => '#e94560',             // Same coral red
            'accent_foreground' => '#ffffff',
            'background' => '#0f0f1a',         // Very dark background
            'foreground' => '#f5f5f5',         // Light text
            'muted' => '#1a1a2e',              // Muted dark backgrounds
            'muted_foreground' => '#9ca3af',   // Muted text
            'border' => '#2d2d44',             // Dark border
            'input' => '#2d2d44',              // Input border
            'ring' => '#e94560',               // Focus ring
            'destructive' => '#ef4444',        // Destructive actions
            'destructive_foreground' => '#ffffff',
        ],
    ],

    'fonts' => [
        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
        'mono' => 'JetBrains Mono, Menlo, Monaco, monospace',
    ],

    'border_radius' => '0.5rem',

    /*
    |--------------------------------------------------------------------------
    | Note-Specific Settings
    |--------------------------------------------------------------------------
    */

    'notes' => [
        'per_page' => 20,
        'max_title_length' => 255,
        'max_excerpt_length' => 500,
        'encryption_algorithm' => 'AES-256-CBC',
        'unique_code_length' => 8,
        'max_failed_decrypt_attempts' => 5,
        'lockout_duration_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'dark_mode' => true,
        'note_encryption' => true,
        'note_sharing' => true,
        'tags' => true,
        'groups' => true,
        'search' => true,
    ],
];
