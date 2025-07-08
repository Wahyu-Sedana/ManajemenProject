<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = auth()->user();

        $canViewAll = $user->hasRole('super_admin') || $user->can('view_any_project');

        if ($canViewAll) {
            $projectsQuery = Project::query();
            $ticketsQuery = Ticket::query();
        } else {
            $projectsQuery = Project::whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

            $ticketsQuery = Ticket::whereHas('project.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $totalProjects = $projectsQuery->count();
        $totalTickets = $ticketsQuery->count();

        $overdueProjects = $projectsQuery
            ->whereDate('end_date', '<', Carbon::today())
            ->count();

        $overdueTickets = DB::table('tickets')
            ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
            ->whereRaw("LOWER(TRIM(ticket_statuses.name)) != 'done'")
            ->whereDate('tickets.due_date', '<', Carbon::today()->toDateString())
            ->count();

        if ($user->hasRole('karyawan')) {
            return [
                Stat::make(__('dashboard.stats.total_projects.title'), $totalProjects)
                    ->description(__('dashboard.stats.total_projects.description'))
                    ->descriptionIcon('heroicon-m-rectangle-stack')
                    ->color('primary'),

                Stat::make(__('dashboard.stats.total_tickets.title'), $totalTickets)
                    ->description(__('dashboard.stats.total_tickets.description'))
                    ->descriptionIcon('heroicon-m-ticket')
                    ->color('success'),

                Stat::make(__('dashboard.stats.tickets_with_overdue.title'), $overdueTickets)
                    ->description(__('dashboard.stats.tickets_with_overdue.description'))
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($overdueTickets > 0 ? 'danger' : 'success'),

                Stat::make(__('dashboard.stats.projects_with_overdue.title'), $overdueProjects)
                    ->description(__('dashboard.stats.projects_with_overdue.description'))
                    ->descriptionIcon('heroicon-m-exclamation-circle')
                    ->color($overdueProjects > 0 ? 'danger' : 'success'),
            ];
        }

        $unassignedTickets = $ticketsQuery->whereNull('user_id')->count();
        $usersCount = User::count();

        return [
            Stat::make(__('dashboard.stats.total_projects.title'), $totalProjects)
                ->description(__('dashboard.stats.total_projects.description'))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make(__('dashboard.stats.total_tickets.title'), $totalTickets)
                ->description(__('dashboard.stats.total_tickets.description'))
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),

            Stat::make(__('dashboard.stats.projects_with_overdue.title'), $overdueProjects)
                ->description(__('dashboard.stats.projects_with_overdue.description'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($overdueProjects > 0 ? 'danger' : 'success'),

            Stat::make(__('dashboard.stats.unassigned_tickets.title'), $unassignedTickets)
                ->description(__('dashboard.stats.unassigned_tickets.description'))
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassignedTickets > 0 ? 'danger' : 'success'),

            Stat::make(__('dashboard.stats.team_members.title'), $usersCount)
                ->description(__('dashboard.stats.team_members.description'))
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make(__('dashboard.stats.tickets_with_overdue.title'), $overdueTickets)
                ->description(__('dashboard.stats.tickets_with_overdue.description'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueTickets > 0 ? 'danger' : 'success'),
        ];
    }
}
