<?php

return [
    'stats' => [
        'total_projects' => [
            'title' => 'Total Proyek',
            'description' => 'Proyek aktif dalam sistem',
        ],
        'total_tickets' => [
            'title' => 'Total Tiket',
            'description' => 'Tiket dari semua proyek',
        ],
        'new_tickets_week' => [
            'title' => 'Tiket Baru Minggu Ini',
            'description' => 'Dibuat dalam 7 hari terakhir',
        ],
        'unassigned_tickets' => [
            'title' => 'Tiket Belum Ditugaskan',
            'description' => 'Tiket tanpa penanggung jawab',
        ],
        'team_members' => [
            'title' => 'Anggota Tim',
            'description' => 'Pengguna yang terdaftar',
        ],
        'projects_with_overdue' => [
            'title' => 'Tiket Terlambat',
            'description' => 'Tiket yang telah lewat tenggat',
        ],
    ],

    'timeline' => [
        'heading' => 'Timeline Proyek',
        'no_projects' => 'Tidak ada proyek tersedia',
        'check_back' => 'Silakan periksa kembali nanti.',
        'days_passed' => ':count hari telah berlalu',
        'days_remaining' => ':count hari tersisa',
        'days_overdue' => ':count hari terlambat',
    ],
];
