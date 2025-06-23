<?php

return [
    'stats' => [
        'total_projects' => [
            'title' => 'Total Projects',
            'description' => 'Active projects in the system',
        ],
        'total_tickets' => [
            'title' => 'Total Tickets',
            'description' => 'Tickets across all projects',
        ],
        'new_tickets_week' => [
            'title' => 'New Tickets This Week',
            'description' => 'Created in the last 7 days',
        ],
        'unassigned_tickets' => [
            'title' => 'Unassigned Tickets',
            'description' => 'Tickets without an assignee',
        ],
        'team_members' => [
            'title' => 'Team Members',
            'description' => 'Registered users',
        ],
        'projects_with_overdue' => [
            'title' => 'Projects with Overdue Tickets',
            'description' => 'Project with overdue tickets',
        ],
    ],

    'timeline' => [
        'heading' => 'Project Timeline',
        'no_projects' => 'No projects available',
        'check_back' => 'Please check back later.',
        'days_passed' => ':count days passed',
        'days_remaining' => ':count days remaining',
        'days_overdue' => ':count days overdue',
    ],
];
