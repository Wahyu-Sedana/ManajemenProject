<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Ticket;
use Auth;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class TicketTimeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ticket-timeline/{project_id?}';

    protected static string $view = 'filament.pages.ticket-timeline';

    public ?string $projectId = null;

    public Collection $projects;

    public ?Project $selectedProject = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.timeline');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.project_visualization');
    }

    public function getTitle(): string
    {
        return __('project.title.ticket_timeline');
    }

    public function mount($project_id = null): void
    {
        $user = Auth::user();

        $this->projects = $user->hasRole('super_admin')
            ? Project::all()
            : $user->projects;

        if ($project_id && $this->projects->contains('id', $project_id)) {
            $this->projectId = $project_id;
            $this->selectedProject = Project::find($project_id);
        } elseif ($this->projects->isNotEmpty() && ! is_null($project_id)) {
            Notification::make()
                ->title(__('project.notifications.project_not_found'))
                ->danger()
                ->send();

            $this->redirect(static::getUrl());
        }
    }

    public function updatedProjectId($value): void
    {
        if ($value) {
            $this->selectProject($value);
        } else {
            $this->selectedProject = null;
            $this->redirect(static::getUrl());
        }
    }

    public function selectProject($projectId): void
    {
        $this->projectId = $projectId;
        $this->selectedProject = Project::find($projectId);

        if ($this->selectedProject) {
            $url = static::getUrl(['project_id' => $projectId]);
            $this->redirect($url);
        }
    }

    public function getTicketsProperty(): Collection
    {
        if (!$this->projectId || !$this->selectedProject) {
            return collect();
        }

        $query = Ticket::query()
            ->with(['status', 'project'])
            ->whereNotNull('due_date')
            ->orderBy('due_date');

        $query->where('project_id', $this->projectId);

        return $query->get();
    }

    public function getMonthHeaders(): array
    {
        $tickets = $this->tickets;

        $earliestDate = null;
        $latestDate = null;

        foreach ($tickets as $ticket) {
            if ($ticket->due_date) {
                $createdAt = $ticket->created_at ?? Carbon::parse($ticket->due_date)->subDays(14);
                $dueDate = Carbon::parse($ticket->due_date);

                $earliestDate = $earliestDate ? min($earliestDate, $createdAt) : $createdAt;
                $latestDate = $latestDate ? max($latestDate, $dueDate) : $dueDate;
            }
        }

        if (!$earliestDate || !$latestDate) {
            $current = Carbon::now()->subMonths(3)->startOfMonth();
            return collect(range(0, 5))->map(fn($i) => $current->copy()->addMonths($i)->format('M Y'))->toArray();
        }

        $months = [];
        $current = $earliestDate->copy()->startOfMonth();
        while ($current <= $latestDate->copy()->endOfMonth()) {
            $months[] = $current->format('M Y');
            $current->addMonth();
        }

        return $months;
    }

    public function getTimelineData(): array
    {
        $tickets = $this->tickets;
        if ($tickets->isEmpty()) {
            return ['tasks' => []];
        }

        $monthHeaders = $this->getMonthHeaders();
        $monthRanges = $this->getMonthDateRanges($monthHeaders);

        $tasks = [];
        $now = Carbon::now();

        foreach ($tickets as $index => $ticket) {
            $startDate = $ticket->created_at ?: Carbon::parse($ticket->due_date)->subDays(14);
            $endDate = Carbon::parse($ticket->due_date);

            $hue = ($index * 137) % 360;
            $color = "hsl({$hue}, 70%, 50%)";
            $remainingDays = $now->diffInDays($endDate, false);

            $barSpans = [];
            foreach ($monthRanges as $monthIndex => $monthRange) {
                if ($startDate <= $monthRange['end'] && $endDate >= $monthRange['start']) {
                    $daysInMonth = $monthRange['start']->daysInMonth;

                    $startPosition = max(0, ($monthRange['start']->diffInDays($startDate, false)) / $daysInMonth * 100);
                    $endPosition = min(100, (($monthRange['start']->diffInDays($endDate, false) + 1) / $daysInMonth * 100));

                    $barSpans[$monthIndex] = [
                        'start_position' => $startPosition,
                        'width_percentage' => $endPosition - $startPosition,
                    ];
                }
            }

            $status = strtolower($ticket->status->name ?? 'default');
            $statusLabel = ucfirst($status);
            $isOverdue = $endDate < $now && !in_array($status, ['completed', 'done', 'closed', 'resolved']);

            $remainingDaysText = match (true) {
                $remainingDays > 0 => __('project.remaining_days', ['count' => $remainingDays]),
                $remainingDays === 0 => __('project.due_today'),
                default => __('project.overdue_days', ['count' => abs($remainingDays)])
            };

            $tasks[] = [
                'id' => $ticket->id,
                'title' => $ticket->name,
                'ticket_id' => $ticket->uuid,
                'color' => $color,
                'bar_spans' => $barSpans,
                'start_date' => $startDate->format('M j'),
                'end_date' => $endDate->format('M j'),
                'remaining_days' => $remainingDays,
                'remaining_days_text' => $remainingDaysText,
                'status' => $status,
                'status_label' => $statusLabel,
                'is_overdue' => $isOverdue,
            ];
        }

        usort($tasks, fn($a, $b) => ($a['is_overdue'] <=> $b['is_overdue']) ?: $a['remaining_days'] <=> $b['remaining_days']);

        return ['tasks' => $tasks];
    }

    private function getMonthDateRanges(array $monthHeaders): array
    {
        return collect($monthHeaders)->mapWithKeys(function ($header, $index) {
            $date = Carbon::createFromFormat('M Y', $header);
            return [$index => ['start' => $date->copy()->startOfMonth(), 'end' => $date->copy()->endOfMonth()]];
        })->toArray();
    }
}
