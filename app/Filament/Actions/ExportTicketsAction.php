<?php

namespace App\Filament\Actions;

use App\Exports\TicketsExport;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTicketsAction
{
    public static function make(): Action
    {
        return Action::make('export_tickets')
            ->label(__('export.action.label'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->form([
                Section::make(__('export.action.section_title'))
                    ->description(__('export.action.description'))
                    ->schema([
                        CheckboxList::make('columns')
                            ->label(__('export.form.columns.label'))
                            ->options([
                                'uuid' => __('export.columns.uuid'),
                                'name' => __('export.columns.name'),
                                'description' => __('export.columns.description'),
                                'status' => __('export.columns.status'),
                                'assignee' => __('export.columns.assignee'),
                                'project' => __('export.columns.project'),
                                // 'epic' => __('export.columns.epic'),
                                'due_date' => __('export.columns.due_date'),
                                'created_at' => __('export.columns.created_at'),
                                'updated_at' => __('export.columns.updated_at'),
                            ])
                            ->default(['uuid', 'name', 'status', 'assignee', 'due_date', 'created_at'])
                            ->required()
                            ->minItems(1)
                            ->columns(2)
                            ->gridDirection('row'),
                    ]),
            ])
            ->action(function (array $data, $livewire): void {
                $livewire->exportTickets($data['columns'] ?? []);
            });
    }
}
