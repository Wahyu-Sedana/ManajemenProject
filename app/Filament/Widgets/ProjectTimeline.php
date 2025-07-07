<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProjectTimeline extends Widget
{
    protected static string $view = 'filament.widgets.project-timeline';

    protected int|string|array $columnSpan = 'full';

    static ?int $sort = 2;

    public function getProjects()
    {
        $query = Project::query()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('name');

        $user = auth()->user();

        $canViewAll = $user && (
            $user->hasRole('super_admin') ||
            $user->can('view_any_project')
        );

        if (! $canViewAll) {
            $query->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        return $query->get();
    }

    protected function getViewData(): array
    {
        $projects = $this->getProjects();
        $today = Carbon::today();
        $timelineData = [];

        foreach ($projects as $project) {
            if (!$project->start_date || !$project->end_date) continue;

            $startDate = Carbon::parse($project->start_date);
            $endDate = Carbon::parse($project->end_date);
            if ($endDate->lt($startDate)) continue;

            $totalDays = $startDate->diffInDays($endDate) + 1;

            $pastDays = $today->lt($startDate) ? 0 : ($today->gt($endDate) ? $totalDays : $startDate->diffInDays($today));
            $remainingDays = $today->lt($startDate) ? $totalDays : ($today->gt($endDate) ? 0 : $today->diffInDays($endDate));
            $progressPercent = ($pastDays / $totalDays) * 100;

            $hasOverdueTicket = DB::table('tickets')
                ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
                ->where('tickets.project_id', $project->id)
                ->whereRaw("LOWER(TRIM(ticket_statuses.name)) != 'done'")
                ->whereDate('tickets.due_date', '<', $today->toDateString())
                ->exists();

           $totalTickets = $project->tickets()->count();

            // Default
            $status = 'In Progress';
            $statusTextColor = 'text-blue-600';
            $progressBarColor = '#2563eb'; // blue

            if ($totalTickets === 0) {
                $status = 'Not Started';
                $statusTextColor = 'text-gray-600';
                $progressBarColor = '#6b7280'; // gray
            } elseif ($hasOverdueTicket && $today->gt($endDate)) {
                $status = 'Overdue';
                $statusTextColor = 'text-red-600';
                $progressBarColor = '#dc2626'; // red
            } elseif ($today->gt($endDate)) {
                $status = 'Completed';
                $statusTextColor = 'text-green-600';
                $progressBarColor = '#16a34a'; // green
            } elseif ($remainingDays <= 0) {
                $status = 'Overdue';
                $statusTextColor = 'text-red-600';
                $progressBarColor = '#dc2626'; // red
            } elseif ($remainingDays <= 7) {
                $status = 'Approaching Deadline';
                $statusTextColor = 'text-yellow-600';
                $progressBarColor = '#eab308'; // yellow
            }


            if ($hasOverdueTicket && $today->gt($endDate)) {
                $status = 'Overdue';
                $statusTextColor = 'text-red-600';
                $progressBarColor = '#dc2626'; // red
            } elseif ($today->gt($endDate)) {
                $status = 'Completed';
                $statusTextColor = 'text-green-600';
                $progressBarColor = '#16a34a'; // green
            } elseif ($remainingDays <= 0) {
                $status = 'Overdue';
                $statusTextColor = 'text-red-600';
                $progressBarColor = '#dc2626'; // red
            } elseif ($remainingDays <= 7) {
                $status = 'Approaching Deadline';
                $statusTextColor = 'text-yellow-600';
                $progressBarColor = '#eab308'; // yellow
            } elseif ($today->lt($startDate)) {
                $status = 'Not Started';
                $statusTextColor = 'text-gray-600';
                $progressBarColor = '#6b7280'; // gray
            }

            $timelineData[] = [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
                'total_days' => $totalDays,
                'past_days' => $pastDays,
                'remaining_days' => $remainingDays,
                'progress_percent' => round($progressPercent, 1),
                'status' => $status,
                'status_text_color' => $statusTextColor,
                'progress_bar_color' => $progressBarColor,
            ];
        }

        // Sort overdue first
        usort($timelineData, function ($a, $b) {
            if ($a['remaining_days'] <= 0 && $b['remaining_days'] > 0) return -1;
            if ($a['remaining_days'] > 0 && $b['remaining_days'] <= 0) return 1;
            return $a['remaining_days'] <=> $b['remaining_days'];
        });

        return ['projects' => $timelineData];
    }
}
