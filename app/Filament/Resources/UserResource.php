<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('user.fields.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label(__('user.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn(string $operation): bool => $operation === 'edit'),

                Forms\Components\TextInput::make('password')
                    ->label(__('user.fields.password'))
                    ->password()
                    ->dehydrateStateUsing(
                        fn($state) => ! empty($state) ? Hash::make($state) : null
                    )
                    ->dehydrated(fn($state) => ! empty($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->label(__('user.fields.roles'))
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('user.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('user.fields.email'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('user.fields.roles'))
                    ->tooltip(fn(User $record): string => $record->roles->pluck('name')->join(', ') ?: __('user.empty.roles'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('projects_count')
                    ->label(__('user.fields.projects'))
                    ->counts('projects')
                    ->tooltip(fn(User $record): string => $record->projects->pluck('name')->join(', ') ?: __('user.empty.projects'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('tickets_count')
                    ->label(__('user.fields.tickets'))
                    ->counts('tickets')
                    ->tooltip(__('user.tooltips.tickets'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('user.fields.created_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('user.fields.updated_at'))
                    ->dateTime()
                    ->sortable()

            ])
            ->filters([
                Tables\Filters\Filter::make('has_projects')
                    ->label(__('user.filters.has_projects'))
                    ->query(fn(Builder $query): Builder => $query->whereHas('projects')),

                Tables\Filters\Filter::make('has_tickets')
                    ->label(__('user.filters.has_tickets'))
                    ->query(fn(Builder $query): Builder => $query->whereHas('tickets')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('user.actions.edit')),
                Tables\Actions\DeleteAction::make()->label(__('project.actions.delete'))
            ]);
    }


    // public static function getRelations(): array
    // {
    //     return [
    //         RelationManagers\ProjectsRelationManager::class,
    //         RelationManagers\TicketsRelationManager::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
