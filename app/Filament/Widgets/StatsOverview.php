<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $totalTickets = Ticket::count();
        $newTicketsLastWeek = Ticket::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $usersCount = User::count();
        $unassignedTickets = Ticket::whereNull('user_id')->count();
        $projectsWithOverdueTickets = Project::whereHas('tickets', function ($query) {
            $query->whereHas('status', function ($statusQuery) {
                $statusQuery->where('name', '!=', 'done');
            })->whereDate('due_date', '<', Carbon::today());
        })->count();

        return [
            Stat::make(__('dashboard.stats.total_projects.title'), $totalProjects)
                ->description(__('dashboard.stats.total_projects.description'))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make(__('dashboard.stats.total_tickets.title'), $totalTickets)
                ->description(__('dashboard.stats.total_tickets.description'))
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),

            Stat::make(__('dashboard.stats.new_tickets_week.title'), $newTicketsLastWeek)
                ->description(__('dashboard.stats.new_tickets_week.description'))
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),

            Stat::make(__('dashboard.stats.unassigned_tickets.title'), $unassignedTickets)
                ->description(__('dashboard.stats.unassigned_tickets.description'))
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassignedTickets > 0 ? 'danger' : 'success'),

            Stat::make(__('dashboard.stats.team_members.title'), $usersCount)
                ->description(__('dashboard.stats.team_members.description'))
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make(__('dashboard.stats.projects_with_overdue.title'), $projectsWithOverdueTickets)
                ->description(__('dashboard.stats.projects_with_overdue.description'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($projectsWithOverdueTickets > 0 ? 'danger' : 'success'),
        ];
    }
}
