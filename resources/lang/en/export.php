<?php

return [
    'action' => [
        'label' => 'Export to Excel',
        'section_title' => 'Select Columns to Export',
        'description' => 'Choose which columns you want to include in the Excel export',
    ],
    'form' => [
        'columns' => [
            'label' => 'Columns',
        ],
    ],
    'columns' => [
        'uuid' => 'Ticket ID',
        'name' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'assignee' => 'Assignee',
        'project' => 'Project',
        // 'epic' => 'Epic',
        'due_date' => 'Due Date',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
];
