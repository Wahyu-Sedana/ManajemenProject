<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('project.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('project.fields.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ticket_prefix')
                    ->label(__('project.fields.ticket_prefix'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('project.fields.start_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('project.fields.end_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('start_date'),
                Forms\Components\Toggle::make('create_default_statuses')
                    ->label(__('project.fields.create_default_statuses'))
                    ->helperText(__('project.fields.create_default_statuses_helper'))
                    ->default(true)
                    ->dehydrated(false)
                    ->visible(fn($livewire) => $livewire instanceof Pages\CreateProject),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('project.fields.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('ticket_prefix')
                    ->label(__('project.fields.ticket_prefix'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('project.fields.start_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('project.fields.end_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_days')
                    ->label(__('project.fields.remaining_days'))
                    ->getStateUsing(function (Project $record): ?string {
                        if (!$record->end_date) return null;
                        return $record->remaining_days . ' ' . __('project.days');
                    })
                    ->badge()
                    ->color(
                        fn(Project $record): string =>
                        !$record->end_date ? 'gray' : ($record->remaining_days <= 0 ? 'danger' : ($record->remaining_days <= 7 ? 'warning' : 'success'))
                    ),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label(__('project.fields.members')),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label(__('project.fields.tickets')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('project.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('project.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('project.actions.edit')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('project.actions.delete')),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            // RelationManagers\TicketStatusesRelationManager::class,
            RelationManagers\MembersRelationManager::class,
            // RelationManagers\EpicsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $userIsSuperAdmin = auth()->user() && (
            (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))
            || (isset(auth()->user()->role) && auth()->user()->role === 'super_admin')
        );

        if (! $userIsSuperAdmin) {
            $query->whereHas('members', function (Builder $query) {
                $query->where('user_id', auth()->id());
            });
        }

        return $query;
    }
}
