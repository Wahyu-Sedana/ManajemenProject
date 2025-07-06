<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';
    protected static ?string $title = 'Project';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('project.fields.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('project.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('project.fields.description'))
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('ticket_prefix')
                    ->label(__('project.fields.ticket_prefix'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tickets_count')
                    ->label(__('project.fields.tickets'))
                    ->counts('tickets')
                    ->sortable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label(__('project.fields.members'))
                    ->counts('members')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('project.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label(__('project.members.actions.add'))
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('project.actions.edit'))
                    ->url(fn($record) => route('filament.admin.resources.projects.edit', $record)),

                Tables\Actions\DetachAction::make()
                    ->label(__('project.members.actions.remove')),
            ]);
    }
}
