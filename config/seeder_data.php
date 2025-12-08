<?php

/**
 * Seeder Data Configuration
 *
 * This file contains all the meaningful data for seeding the database.
 * Run: php artisan db:seed --class=DemoDataSeeder
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Password
    |--------------------------------------------------------------------------
    */
    'default_password' => '12341234',

    /*
    |--------------------------------------------------------------------------
    | Default Encryption Code
    |--------------------------------------------------------------------------
    */
    'encryption_code' => '1234',

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    | 10 users with unique names, emails, and colors
    */
    'users' => [
        [
            'name' => 'Alex Thompson',
            'email' => 'alex@example.com',
            'color' => '#ef4444', // Red
        ],
        [
            'name' => 'Sarah Chen',
            'email' => 'sarah@example.com',
            'color' => '#f97316', // Orange
        ],
        [
            'name' => 'Michael Rodriguez',
            'email' => 'michael@example.com',
            'color' => '#eab308', // Yellow
        ],
        [
            'name' => 'Emma Wilson',
            'email' => 'emma@example.com',
            'color' => '#22c55e', // Green
        ],
        [
            'name' => 'James Parker',
            'email' => 'james@example.com',
            'color' => '#14b8a6', // Teal
        ],
        [
            'name' => 'Olivia Martinez',
            'email' => 'olivia@example.com',
            'color' => '#3b82f6', // Blue
        ],
        [
            'name' => 'Daniel Kim',
            'email' => 'daniel@example.com',
            'color' => '#6366f1', // Indigo
        ],
        [
            'name' => 'Sophia Anderson',
            'email' => 'sophia@example.com',
            'color' => '#8b5cf6', // Violet
        ],
        [
            'name' => 'William Brown',
            'email' => 'william@example.com',
            'color' => '#ec4899', // Pink
        ],
        [
            'name' => 'Isabella Garcia',
            'email' => 'isabella@example.com',
            'color' => '#06b6d4', // Cyan
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags (Per User)
    |--------------------------------------------------------------------------
    | Each user gets 10 tags with different colors
    */
    'tags' => [
        ['name' => 'Work', 'color' => '#ef4444'],
        ['name' => 'Personal', 'color' => '#3b82f6'],
        ['name' => 'Ideas', 'color' => '#eab308'],
        ['name' => 'Important', 'color' => '#dc2626'],
        ['name' => 'Research', 'color' => '#8b5cf6'],
        ['name' => 'Projects', 'color' => '#22c55e'],
        ['name' => 'Learning', 'color' => '#f97316'],
        ['name' => 'Health', 'color' => '#14b8a6'],
        ['name' => 'Finance', 'color' => '#6366f1'],
        ['name' => 'Travel', 'color' => '#ec4899'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups (Per User)
    |--------------------------------------------------------------------------
    | Each user gets 3 groups
    */
    'groups' => [
        [
            'name' => 'Work Projects',
            'description' => 'All work-related projects and tasks',
            'color' => '#3b82f6',
        ],
        [
            'name' => 'Personal Life',
            'description' => 'Personal notes and reminders',
            'color' => '#22c55e',
        ],
        [
            'name' => 'Archive',
            'description' => 'Completed and archived items',
            'color' => '#6b7280',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notes (Per User)
    |--------------------------------------------------------------------------
    | Each user gets 10 notes. Flags are applied dynamically in the seeder.
    | Content is plain text format.
    */
    'notes' => [
        [
            'title' => 'Project Kickoff Meeting Notes',
            'content' => "Meeting Summary

Today we discussed the new product launch scheduled for Q2. Key points covered:

• Timeline: 12 weeks development cycle
• Budget: Approved for \$50,000
• Team: 5 developers, 2 designers, 1 PM

Action items:
1. Create detailed project plan by Friday
2. Schedule design review for next week
3. Set up development environment

Next meeting scheduled for Monday at 10 AM.",
            'color' => '#3b82f6',
        ],
        [
            'title' => 'Weekly Goals and Priorities',
            'content' => "This Week's Focus

Priority tasks for the week ahead:

HIGH:
• Complete API integration
• Review pull requests

MEDIUM:
• Update documentation

LOW:
• Refactor legacy code

Remember to take breaks and stay hydrated!

\"Success is the sum of small efforts repeated day in and day out.\"",
            'color' => '#22c55e',
        ],
        [
            'title' => 'Grocery Shopping List',
            'content' => "Weekly Groceries

PRODUCE:
• Apples (6)
• Bananas (bunch)
• Spinach (2 bags)
• Tomatoes (4)
• Onions (3)

DAIRY:
• Milk (1 gallon)
• Greek yogurt (4 cups)
• Cheese (cheddar block)

PROTEINS:
• Chicken breast (2 lbs)
• Salmon fillets (4)
• Eggs (dozen)",
            'color' => '#f97316',
        ],
        [
            'title' => 'Book Recommendations',
            'content' => "Reading List 2024

Books I want to read this year:

NON-FICTION:
• Atomic Habits by James Clear
• Deep Work by Cal Newport
• The Psychology of Money by Morgan Housel

FICTION:
• Project Hail Mary by Andy Weir
• The Midnight Library by Matt Haig

Currently reading: Thinking, Fast and Slow",
            'color' => '#8b5cf6',
        ],
        [
            'title' => 'Workout Routine',
            'content' => "Weekly Workout Plan

MONDAY - Chest & Triceps
• Bench Press: 4x8
• Incline Dumbbell Press: 3x10
• Cable Flyes: 3x12
• Tricep Dips: 3x10

WEDNESDAY - Back & Biceps
• Deadlifts: 4x6
• Pull-ups: 3x8
• Barbell Rows: 3x10
• Bicep Curls: 3x12

FRIDAY - Legs & Shoulders
• Squats: 4x8
• Leg Press: 3x10
• Shoulder Press: 3x10
• Lateral Raises: 3x12",
            'color' => '#14b8a6',
        ],
        [
            'title' => 'API Integration Notes',
            'content' => "REST API Documentation

Endpoints for the payment gateway integration:

POST /api/v1/payments
GET /api/v1/payments/{id}
PUT /api/v1/payments/{id}/refund

AUTHENTICATION:
Use Bearer token in headers:
Authorization: Bearer {api_key}

ERROR CODES:
• 400 - Bad Request
• 401 - Unauthorized
• 404 - Not Found
• 500 - Server Error

Note: Rate limit is 100 requests per minute.",
            'color' => '#6366f1',
        ],
        [
            'title' => 'Travel Plans - Japan Trip',
            'content' => "Japan Itinerary

DAY 1-3: TOKYO
• Shibuya Crossing
• Senso-ji Temple
• teamLab Borderless
• Tsukiji Fish Market

DAY 4-5: KYOTO
• Fushimi Inari Shrine
• Arashiyama Bamboo Grove
• Kinkaku-ji Temple

DAY 6-7: OSAKA
• Osaka Castle
• Dotonbori District
• Universal Studios Japan

Budget: ~\$3,000 for 7 days
Flight: March 15-22",
            'color' => '#ec4899',
        ],
        [
            'title' => 'Meeting with Investors',
            'content' => "Investor Pitch Prep

Date: Next Thursday at 2 PM
Location: Conference Room A

AGENDA:
1. Company overview (5 min)
2. Problem & Solution (10 min)
3. Market opportunity (5 min)
4. Business model (5 min)
5. Traction & metrics (10 min)
6. Financial projections (5 min)
7. Q&A (20 min)

KEY METRICS TO HIGHLIGHT:
• MRR: \$125,000
• Growth: 15% MoM
• Churn: 2.5%
• CAC: \$85
• LTV: \$850",
            'color' => '#eab308',
        ],
        [
            'title' => 'Password Recovery Codes',
            'content' => "Recovery Information

This note contains sensitive backup codes.

EMAIL RECOVERY:
• Code 1: XXXX-XXXX-XXXX
• Code 2: YYYY-YYYY-YYYY

2FA BACKUP CODES:
• 8294-3847-2938
• 9283-4729-3847
• 3847-2938-4729

Keep these codes safe and secure!",
            'color' => '#dc2626',
            'encryption_hint' => 'Four digit code',
        ],
        [
            'title' => 'Financial Planning Notes',
            'content' => "Monthly Budget

INCOME:
• Salary: \$5,500
• Side projects: \$800
• Total: \$6,300

EXPENSES:
• Rent: \$1,800
• Utilities: \$150
• Groceries: \$400
• Transportation: \$200
• Entertainment: \$300
• Savings: \$1,500

INVESTMENT GOALS:
• Emergency fund: 6 months (75% complete)
• Retirement: Max 401k contributions
• Index funds: \$500/month",
            'color' => '#6366f1',
            'encryption_hint' => 'Simple numeric',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Note Colors (for random assignment)
    |--------------------------------------------------------------------------
    */
    'note_colors' => [
        '#ef4444', // Red
        '#f97316', // Orange
        '#eab308', // Yellow
        '#22c55e', // Green
        '#14b8a6', // Teal
        '#3b82f6', // Blue
        '#6366f1', // Indigo
        '#8b5cf6', // Violet
        '#ec4899', // Pink
        null,      // No color (default)
    ],
];
