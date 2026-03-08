<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

class Notifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.notifications';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notification History';

    protected static ?int $navigationSort = 100;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', auth()->id())
                    ->where('data->format', 'filament')
                    ->latest()
            )
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-s-bell')
                    ->color(fn ($state) => $state ? 'gray' : 'primary')
                    ->tooltip(fn ($state) => $state ? 'Read' : 'Unread'),
                IconColumn::make('data.icon')
                    ->label('')
                    ->icon(fn ($state) => $state ?? 'heroicon-o-bell')
                    ->color(fn ($record) => $record->data['iconColor'] ?? 'gray'),
                TextColumn::make('data.title')
                    ->label('Title')
                    ->weight(fn ($record) => $record->read_at ? 'normal' : 'bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->title', 'like', "%{$search}%");
                    }),
                TextColumn::make('data.body')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->data['body'] ?? null)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->body', 'like', "%{$search}%");
                    }),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                TextColumn::make('read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not read')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'unread' => $query->whereNull('read_at'),
                            'read' => $query->whereNotNull('read_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->label('View')
                    ->url(function (DatabaseNotification $record): ?string {
                        $taskId = $record->data['actions'][0]['url'] ?? null;
                        if ($taskId) {
                            return $taskId;
                        }

                        return null;
                    })
                    ->visible(fn (DatabaseNotification $record) => isset($record->data['actions'][0]['url']))
                    ->action(function (DatabaseNotification $record): void {
                        if (! $record->read_at) {
                            $record->markAsRead();
                        }
                    }),
                Action::make('markAsRead')
                    ->icon('heroicon-o-check')
                    ->label('Mark as Read')
                    ->visible(fn (DatabaseNotification $record) => ! $record->read_at)
                    ->action(fn (DatabaseNotification $record) => $record->markAsRead()),
                Action::make('markAsUnread')
                    ->icon('heroicon-o-bell')
                    ->label('Mark as Unread')
                    ->visible(fn (DatabaseNotification $record) => $record->read_at !== null)
                    ->action(fn (DatabaseNotification $record) => $record->update(['read_at' => null])),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (DatabaseNotification $record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkAction::make('markAsRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(fn (Collection $records) => $records->each->markAsRead())
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('markAsUnread')
                    ->label('Mark as Unread')
                    ->icon('heroicon-o-bell')
                    ->action(fn (Collection $records) => $records->each(fn ($r) => $r->update(['read_at' => null])))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->delete())
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No notifications')
            ->emptyStateDescription('You\'re all caught up!')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', auth()->id())
            ->where('data->format', 'filament')
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
