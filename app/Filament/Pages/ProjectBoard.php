<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use App\Filament\Actions\ExportTicketsAction;
use App\Exports\TicketsExport;
use Illuminate\Contracts\Support\Htmlable;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $view = 'filament.pages.project-board';

    public function getTitle(): string
    {
        return __('navigation.labels.project_board');
    }


    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.project_board');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.project_visualization');
    }

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'project-board/{project_id?}';

    public ?Project $selectedProject = null;

    public Collection $projects;

    public Collection $ticketStatuses;

    public ?Ticket $selectedTicket = null;

    public ?int $selectedProjectId = null;

    public function mount($project_id = null): void
    {
        if (auth()->user()->hasRole(['super_admin'])) {
            $this->projects = Project::all();
        } else {
            $this->projects = auth()->user()->projects;
        }

        if ($project_id && $this->projects->contains('id', $project_id)) {
            $this->selectedProjectId = (int) $project_id;
            $this->selectedProject = Project::find($project_id);
            $this->loadTicketStatuses();
        } elseif ($this->projects->isNotEmpty() && ! is_null($project_id)) {
            Notification::make()
                ->title(__('board.notifications.project_not_found'))
                ->danger()
                ->send();
            $this->redirect(static::getUrl());
        }
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedTicket = null;
        $this->ticketStatuses = collect();
        $this->selectedProjectId = $projectId;
        $this->selectedProject = Project::find($projectId);

        if ($this->selectedProject) {
            $url = static::getUrl(['project_id' => $projectId]);
            $this->redirect($url);

            $this->loadTicketStatuses();
        }
    }

    public function updatedSelectedProjectId($value): void
    {
        if ($value) {
            $this->selectProject((int) $value);
        } else {
            $this->selectedProject = null;
            $this->ticketStatuses = collect();

            $this->redirect(static::getUrl());
        }
    }

    public function loadTicketStatuses(): void
    {
        if (! $this->selectedProject) {
            $this->ticketStatuses = collect();
            return;
        }

        $user = auth()->user();

        $this->ticketStatuses = $this->selectedProject->ticketStatuses()
            ->with(['tickets' => function ($query) use ($user) {
                $query->with(['assignee', 'status']);

                if (!$user->can('viewAny', \App\Models\Ticket::class)) {
                    $query->where('user_id', $user->id);
                }

                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    #[On('ticket-moved')]
    public function moveTicket($ticketId, $newStatusId): void
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket && $ticket->project_id === $this->selectedProject?->id) {
            $ticket->update([
                'ticket_status_id' => $newStatusId,
            ]);

            $this->loadTicketStatuses();

            $this->dispatch('ticket-updated');

            Notification::make()
                ->title(__('board.notifications.ticket_updated'))
                ->success()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    public function showTicketDetails(int $ticketId): void
    {
        $ticket = Ticket::with(['assignee', 'status', 'project'])->find($ticketId);

        if (! $ticket) {
            Notification::make()
                ->title(__('board.notifications.ticket_not_found'))
                ->danger()
                ->send();

            return;
        }


        $url = TicketResource::getUrl('view', ['record' => $ticketId]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function closeTicketDetails(): void
    {
        $this->selectedTicket = null;
    }

    public function editTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $this->canEditTicket($ticket)) {
            Notification::make()
                ->title(__('board.notifications.permission_denied'))
                ->body('You do not have permission to edit this ticket.')
                ->danger()
                ->send();

            return;
        }

        $this->redirect(TicketResource::getUrl('edit', ['record' => $ticketId]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->label(__('board.actions.new_ticket'))
                ->icon('heroicon-m-plus')
                ->visible(fn() => $this->selectedProject !== null && auth()->user()->hasRole(['super_admin']))
                ->url(fn(): string => TicketResource::getUrl('create', [
                    'project_id' => $this->selectedProject?->id,
                    'ticket_status_id' => $this->selectedProject?->ticketStatuses->first()?->id,
                ])),


            Action::make('refresh_board')
                ->label(__('board.actions.refresh_board'))
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard')
                ->color('warning'),

            ExportTicketsAction::make()
                ->visible(fn() => $this->selectedProject !== null && auth()->user()->hasAnyRole(['super_admin', 'hrd', 'supervisor'])),

        ];
    }

    private function canViewTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canEditTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canManageTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }


    public function exportTickets(array $selectedColumns): void
    {
        if (empty($selectedColumns)) {
            Notification::make()
                ->title(__('board.notifications.export_failed'))
                ->body(__('board.notifications.select_columns'))
                ->danger()
                ->send();
            return;
        }

        $tickets = collect();

        if ($this->selectedProject) {
            $tickets = $this->selectedProject->tickets()
                ->with(['assignee', 'status', 'project'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($this->ticketStatuses->isNotEmpty()) {
            $ticketIds = $this->ticketStatuses->flatMap(function ($status) {
                return $status->tickets->pluck('id');
            });

            $tickets = Ticket::whereIn('id', $ticketIds)
                ->with(['assignee', 'status', 'project'])
                ->orderBy('created_at', 'asc')
                ->get();
        }

        if ($tickets->isEmpty()) {
            Notification::make()
                ->title(__('board.notifications.export_failed'))
                ->body(__('board.notifications.no_tickets'))
                ->warning()
                ->send();
            return;
        }

        try {
            $fileName = 'tickets_' . ($this->selectedProject?->name ?? 'export') . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            $fileName = \Illuminate\Support\Str::slug($fileName, '_') . '.xlsx';
            $export = new TicketsExport($tickets, $selectedColumns);
            Excel::store($export, 'exports/' . $fileName, 'public');
            $downloadUrl = asset('storage/exports/' . $fileName);
            $this->js("
                fetch('{$downloadUrl}')
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = '{$fileName}';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    });
            ");

            Notification::make()
                ->title(__('board.notifications.export_success'))
                ->body(__('board.notifications.download_started'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('board.notifications.export_failed'))
                ->body(__('board.notifications.export_error', ['message' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}
