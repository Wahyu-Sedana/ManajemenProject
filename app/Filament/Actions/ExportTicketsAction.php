<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use App\Exports\TicketsExport;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ExportTicketsAction
{
    public static function make(): Action
    {
        return Action::make('export_tickets')
            ->label(__('export.action.label'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->action(function ($livewire): void {
                $defaultColumns = [
                    'uuid',
                    'name',
                    'description',
                    'status',
                    'assignee',
                    'project',
                    'due_date',
                    'created_at',
                    'updated_at',
                ];

                $livewire->exportTickets($defaultColumns);
            });
    }
}

