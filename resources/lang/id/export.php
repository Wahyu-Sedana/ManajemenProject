<?php

return [
    'action' => [
        'label' => 'Ekspor ke Excel',
        'section_title' => 'Pilih Kolom untuk Diekspor',
        'description' => 'Pilih kolom yang ingin Anda sertakan dalam ekspor Excel',
    ],
    'form' => [
        'columns' => [
            'label' => 'Kolom',
        ],
    ],
    'columns' => [
        'uuid' => 'ID Tiket',
        'name' => 'Judul',
        'description' => 'Deskripsi',
        'status' => 'Status',
        'assignee' => 'Penanggung Jawab',
        'project' => 'Proyek',
        // 'epic' => 'Epic',
        'due_date' => 'Batas Waktu',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada',
    ],
];
