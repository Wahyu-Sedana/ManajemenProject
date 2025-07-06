<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.tickets');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.project_management');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()->hasRole(['super_admin'])) {
            $query->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('project.members', function ($query) {
                        $query->where('users.id', auth()->id());
                    });
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $projectId = request()->query('project_id') ?? request()->input('project_id');
        $statusId = request()->query('ticket_status_id') ?? request()->input('ticket_status_id');

        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->label(__('tickets.project'))
                    ->options(function () {
                        if (auth()->user()->hasRole(['super_admin'])) {
                            return Project::pluck('name', 'id')->toArray();
                        }

                        return auth()->user()->projects()->pluck('name', 'projects.id')->toArray();
                    })
                    ->default($projectId)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('ticket_status_id', null);
                        $set('user_id', null);
                        // $set('epic_id', null);
                    }),

                Forms\Components\Select::make('ticket_status_id')
                    ->label(__('tickets.status'))
                    ->options(function ($get) {
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return [];
                        }

                        return TicketStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default($statusId)
                    ->required()
                    ->searchable()
                    ->preload(),
                // Forms\Components\Select::make('epic_id')
                //     ->label('Epic')
                //     ->options(function (callable $get) {
                //         $projectId = $get('project_id');

                //         if (!$projectId) {
                //             return [];
                //         }

                //         return Epic::where('project_id', $projectId)
                //             ->pluck('name', 'id')
                //             ->toArray();
                //     })
                //     ->searchable()
                //     ->preload()
                //     ->nullable()
                //     ->hidden(fn(callable $get): bool => !$get('project_id')),

                Forms\Components\TextInput::make('name')
                    ->label(__('tickets.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\RichEditor::make('description')
                    ->label(__('tickets.description'))
                    ->fileAttachmentsDirectory('attachments')
                    ->columnSpanFull(),

                Forms\Components\Select::make('user_id')
                    ->label(__('tickets.assignee'))
                    ->helperText(__('tickets.helper_assignee'))
                    ->options(function ($get) {
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return [];
                        }

                        $project = Project::find($projectId);
                        if (! $project) {
                            return [];
                        }

                        return $project->members()
                            ->select('users.id', 'users.name')
                            ->pluck('users.name', 'users.id')
                            ->toArray();
                    })
                    ->default(function () {
                        return auth()->id();
                    })
                    ->required(),

                Forms\Components\DatePicker::make('due_date')
                    ->label(__('tickets.due_date'))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label(__('tickets.id'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('tickets.project'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('tickets.name'))
                    ->searchable()
                    ->limit(30),


                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('tickets.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label(__('tickets.assignee'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('tickets.due_date'))
                    ->date()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('epic.name')
                //     ->label('Epic')
                //     ->sortable()
                //     ->searchable()
                //     ->default('—')
                //     ->placeholder('No Epic'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('tickets.view.created_at'))
                    ->dateTime()
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label(__('tickets.view.project'))
                    ->options(function () {
                        if (auth()->user()->hasRole(['super_admin'])) {
                            return Project::pluck('name', 'id')->toArray();
                        }

                        return auth()->user()->projects()->pluck('name', 'projects.id')->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('ticket_status_id')
                    ->label(__('tickets.view.status'))
                    ->options(function () {
                        $projectId = request()->input('tableFilters.project_id');

                        if (!$projectId) {
                            return [];
                        }

                        return TicketStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                // Tables\Filters\SelectFilter::make('epic_id')
                //     ->label('Epic')
                //     ->options(function () {
                //         $projectId = request()->input('tableFilters.project_id');

                //         if (!$projectId) {
                //             return [];
                //         }

                //         return Epic::where('project_id', $projectId)
                //             ->pluck('name', 'id')
                //             ->toArray();
                //     })
                //     ->searchable()
                //     ->preload(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('tickets.view.assignee'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('due_date')
                    ->label(__('tickets.view.due_date'))
                    ->form([
                        Forms\Components\DatePicker::make('due_from'),
                        Forms\Components\DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery();

        return $query->count();
    }
}
