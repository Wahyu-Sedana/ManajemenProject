<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\TicketStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';
        public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('project.fields.tickets');
    }

    public static function getPluralLabel(): string
    {
        return __('project.fields.tickets');
    }


    public function form(Form $form): Form
    {
        $projectId = $this->getOwnerRecord()->id;
        $defaultStatus = TicketStatus::where('project_id', $projectId)->first();
        $defaultStatusId = $defaultStatus?->id;

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('tickets.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('ticket_status_id')
                    ->label(__('tickets.status'))
                    ->options(function () use ($projectId) {
                        return TicketStatus::where('project_id', $projectId)->pluck('name', 'id')->toArray();
                    })
                    ->default($defaultStatusId)
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('user_id')
                    ->label(__('tickets.assignee'))
                    ->options(fn() => $this->getOwnerRecord()->members()->pluck('name', 'users.id')->toArray())
                    ->searchable()
                    ->nullable(),

                Forms\Components\DatePicker::make('due_date')
                    ->label(__('tickets.due_date'))
                    ->nullable(),

                Forms\Components\RichEditor::make('description')
                    ->label(__('tickets.description'))
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label(__('tickets.id'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('tickets.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('tickets.status'))
                    ->badge()
                    ->color(fn($record) => match ($record->status?->name) {
                        'To Do' => 'warning',
                        'In Progress' => 'info',
                        'Review' => 'primary',
                        'Done' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label(__('tickets.assignee'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('tickets.due_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('tickets.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ticket_status_id')
                    ->label(__('tickets.status'))
                    ->options(fn() => TicketStatus::where('project_id', $this->getOwnerRecord()->id)->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('tickets.assignee'))
                    ->options(fn() => $this->getOwnerRecord()->members()->pluck('name', 'users.id')->toArray()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('tickets.actions.create')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('tickets.actions.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('tickets.actions.delete')),
            ]);
    }
}
