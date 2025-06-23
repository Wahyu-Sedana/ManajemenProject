<?php

return [
    'users' => 'Pengguna',
    'fields' => [
        'name' => 'Nama',
        'email' => 'Email',
        'password' => 'Kata Sandi',
        'roles' => 'Peran',
        'projects' => 'Proyek',
        'tickets' => 'Tiket',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada',
    ],
    'filters' => [
        'has_projects' => 'Memiliki Proyek',
        'has_tickets' => 'Memiliki Tiket',
    ],
    'tooltips' => [
        'tickets' => 'Jumlah tiket yang ditugaskan ke pengguna ini',
    ],
    'empty' => [
        'roles' => 'Tidak ada peran',
        'projects' => 'Tidak ada proyek',
    ],
    'actions' => [
        'edit' => 'Ubah',
        'delete' => 'Hapus',
    ],
];
