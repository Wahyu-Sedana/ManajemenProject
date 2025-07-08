<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Pages\ProjectBoard;
use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public ?int $editingCommentId = null;

    protected function getHeaderActions(): array
    {
        $ticket = $this->getRecord();
        $project = $ticket->project;
        $canComment = auth()->user()->hasRole(['super_admin'])
            || $project->members()->where('users.id', auth()->id())->exists();

        return [
            Actions\EditAction::make()
                ->visible(function () {
                    $ticket = $this->getRecord();

                    return auth()->user()->hasRole(['super_admin'])
                        || $ticket->user_id === auth()->id();
                }),

            Actions\Action::make('addComment')
                ->label(__('tickets.actions.add_comment'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->form([
                    RichEditor::make('comment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $ticket = $this->getRecord();

                    $fixedComment = str_replace(
                        'http://localhost',
                        config('app.url'),
                        $data['comment']
                    );

                    $ticket->comments()->create([
                        'user_id' => auth()->id(),
                        'comment' => $fixedComment,
                    ]);

                    Notification::make()
                        ->title(__('tickets.notifications.comment_added'))
                        ->success()
                        ->send();
                })
                ->visible($canComment),

            Action::make('back')
                ->label(__('tickets.actions.back_to_board'))
                ->color('gray')
                ->url(fn() => ProjectBoard::getUrl(['project_id' => $this->record->project_id])),
        ];
    }

    public function handleEditComment($id)
    {
        $comment = TicketComment::find($id);

        if (! $comment) {
            Notification::make()
                ->title(__('tickets.notifications.comment_not_found'))
                ->danger()
                ->send();

            return;
        }

        // Check permissions
        if (! auth()->user()->hasRole(['super_admin']) && $comment->user_id !== auth()->id()) {
            Notification::make()
                ->title(__('tickets.notifications.permission_denied'))
                ->danger()
                ->send();

            return;
        }

        $this->editingCommentId = $id; // Set ID komentar yang sedang diedit
        $this->mountAction('editComment', ['commentId' => $id]);
    }

    public function handleDeleteComment($id)
    {
        $comment = TicketComment::find($id);

        if (! $comment) {
            Notification::make()
                ->title(__('tickets.notifications.comment_not_found'))
                ->danger()
                ->send();

            return;
        }

        // Check permissions
        if (! auth()->user()->hasRole(['super_admin']) && $comment->user_id !== auth()->id()) {
            Notification::make()
                ->title(__('tickets.notifications.permission_denied'))
                ->danger()
                ->send();

            return;
        }

        $comment->delete();

        Notification::make()
            ->title(__('tickets.notifications.comment_deleted'))
            ->success()
            ->send();

        // Refresh the page
        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('uuid')->label(__('tickets.view.id'))
                                        ->copyable(),
                                    TextEntry::make('name')->label(__('tickets.view.name')),
                                    TextEntry::make('project.name')->label(__('tickets.view.project')),
                                ]),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('status.name')->label(__('tickets.view.status'))
                                        ->badge()
                                        ->color(fn($state) => match ($state) {
                                            'To Do' => 'warning',
                                            'In Progress' => 'info',
                                            'Review' => 'primary',
                                            'Done' => 'success',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('assignee.name')->label(__('tickets.view.assignee')),
                                    TextEntry::make('due_date')->label(__('tickets.view.due_date'))
                                        ->date(),
                                ]),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('created_at')->label(__('tickets.view.created_at'))
                                        ->dateTime(),

                                    TextEntry::make('updated_at')->label(__('tickets.view.updated_at'))
                                        ->dateTime(),
                                ]),
                        ])->columnSpan(1),
                    ]),

                Section::make(__('tickets.view.description'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(fn($state) => '
                            <style>
                                .prose img {
                                    display: block;
                                    max-width: 100%;
                                    height: auto;
                                    width: auto;
                                    max-height: 400px;
                                    object-fit: contain;
                                    image-orientation: from-image;
                                    margin-left: 0;
                                    margin-right: auto;
                                }
                            </style>
                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                ' . $state . '
                            </div>
                        '),
                    ]),
                Section::make(__('tickets.view.comments'))
                    ->description(__('tickets.view.comments_description'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextEntry::make('comments_list')
                            ->label(__('tickets.view.recent_comments'))
                            ->state(function (Ticket $record) {
                                if (method_exists($record, 'comments')) {
                                    return $record->comments()->with('user')->latest()->get();
                                }

                                return collect();
                            })
                            ->view('filament.resources.ticket-resource.latest-comments'),
                    ])
                    ->collapsible(),

                // Section::make(__('tickets.view.history'))
                //     ->icon('heroicon-o-clock')
                //     ->collapsible()
                //     ->schema([
                //         TextEntry::make('histories')
                //             ->hiddenLabel()
                //             ->view('filament.resources.ticket-resource.timeline-history'),
                //     ]),
            ]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('editComment')
                ->label(__('tickets.actions.edit_comment'))
                ->modalHeading(__('tickets.actions.edit_comment'))
                ->modalSubmitActionLabel(__('tickets.actions.update'))

                ->mountUsing(function (Forms\Form $form, array $arguments) {
                    $commentId = $arguments['commentId'] ?? null;

                    if (! $commentId) {
                        return;
                    }

                    $comment = TicketComment::find($commentId);

                    if (! $comment) {
                        return;
                    }

                    $form->fill([
                        'commentId' => $comment->id,
                        'comment' => $comment->comment,
                    ]);
                })
                ->form([
                    Hidden::make('commentId')
                        ->required(),
                    RichEditor::make('comment')
                        ->label('Comment')
                        ->toolbarButtons([
                            'blockquote',
                            'bold',
                            'bulletList',
                            'codeBlock',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'underline',
                            'undo',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $comment = TicketComment::find($data['commentId']);

                    if (! $comment) {
                        Notification::make()
                            ->title(__('tickets.notifications.comment_not_found'))
                            ->danger()
                            ->send();

                        return;
                    }

                    // Check permissions
                    if (! auth()->user()->hasRole(['super_admin']) && $comment->user_id !== auth()->id()) {
                        Notification::make()
                            ->title(__('tickets.notifications.permission_denied'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $comment->update([
                        'comment' => $data['comment'],
                    ]);

                    Notification::make()
                        ->title(__('tickets.notifications.comment_updated'))
                        ->success()
                        ->send();

                    // Reset editingCommentId
                    $this->editingCommentId = null;

                    // Refresh the page
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                })
                ->modalWidth('lg')
                ->color('success')
                ->icon('heroicon-o-pencil'),
        ];
    }
}
