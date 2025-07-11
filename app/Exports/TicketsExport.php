<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $tickets;
    protected $selectedColumns;
    protected $availableColumns = [
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
    ];

    public function __construct(Collection $tickets, array $selectedColumns)
    {
        $this->tickets = $tickets;
        $this->selectedColumns = $selectedColumns;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            if (isset($this->availableColumns[$column])) {
                $headings[] = $this->availableColumns[$column];
            }
        }
        return $headings;
    }

    public function map($ticket): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'uuid':
                    $row[] = $ticket->uuid;
                    break;
                case 'name':
                    $row[] = $ticket->name;
                    break;
                case 'description':
                    $description = $ticket->description ?? '';
                    $imageUrl = null;
                    if (preg_match('/<img[^>]+src="([^">]+)"/i', $description, $matches)) {
                        $src = $matches[1];
                        $imageUrl = str_starts_with($src, '/') ? url($src) : $src;
                    }

                    $cleanText = preg_replace('/<img[^>]*>/i', '', $description); 
                    $textContent = trim(strip_tags($cleanText));

                    $textContent = preg_replace('/Photo on .*?\.(jpg|png|jpeg|gif)\s*\d+(\.\d+)?\s*(KB|MB)/i', '', $textContent);

                    $textContent = preg_replace('/\s+/', ' ', $textContent);
                    $textContent = trim($textContent);

                    $textContent = \Illuminate\Support\Str::limit($textContent, 100);

                    if ($imageUrl && $textContent) {
                        $row[] = "{$imageUrl} | {$textContent}";
                    } elseif ($imageUrl) {
                        $row[] = $imageUrl;
                    } elseif ($textContent) {
                        $row[] = $textContent;
                    } else {
                        $row[] = '';
                    }
                    break;
                case 'status':
                    $row[] = $ticket->status?->name ?? 'No Status';
                    break;
                case 'assignee':
                    $row[] = $ticket->assignee?->name ?? 'Unassigned';
                    break;
                case 'project':
                    $row[] = $ticket->project?->name ?? 'No Project';
                    break;
                case 'due_date':
                    $row[] = $ticket->due_date ? $ticket->due_date->format('Y-m-d') : '';
                    break;
                case 'created_at':
                    $row[] = $ticket->created_at->format('Y-m-d H:i:s');
                    break;
                case 'updated_at':
                    $row[] = $ticket->updated_at->format('Y-m-d H:i:s');
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF366092',
                    ],
                ],
                'font' => [
                    'color' => [
                        'argb' => 'FFFFFFFF',
                    ],
                    'bold' => true,
                ],
            ],
        ];
    }

    public function getAvailableColumns(): array
    {
        return $this->availableColumns;
    }
}
