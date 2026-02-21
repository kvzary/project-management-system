<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\TeamWorkMonitor;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TeamProductivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Team Productivity';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereHas('assignedTasks')
                    ->withCount([
                        'assignedTasks as total_tasks' => fn (Builder $q) => $q->whereNull('deleted_at'),
                        'assignedTasks as completed_tasks' => fn (Builder $q) => $q->whereNull('deleted_at')->whereIn('status', ['done', 'completed']),
                        'assignedTasks as in_progress_tasks' => fn (Builder $q) => $q->whereNull('deleted_at')->where('status', 'in_progress'),
                        'assignedTasks as overdue_tasks' => fn (Builder $q) => $q->whereNull('deleted_at')
                            ->whereNotNull('due_date')
                            ->where('due_date', '<', now())
                            ->whereNotIn('status', ['done', 'completed']),
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Team Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_tasks')
                    ->label('Total')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('completed_tasks')
                    ->label('Completed')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                TextColumn::make('in_progress_tasks')
                    ->label('In Progress')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                TextColumn::make('overdue_tasks')
                    ->label('Overdue')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('completion_rate')
                    ->label('Rate')
                    ->getStateUsing(fn ($record) => $record->total_tasks > 0
                        ? round(($record->completed_tasks / $record->total_tasks) * 100) . '%'
                        : '0%')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->total_tasks === 0 => 'gray',
                        ($record->completed_tasks / max($record->total_tasks, 1)) >= 0.7 => 'success',
                        ($record->completed_tasks / max($record->total_tasks, 1)) >= 0.4 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->actions([
                Action::make('viewWork')
                    ->label('View Work')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => TeamWorkMonitor::getUrl(['selectedUserId' => $record->id])),
            ])
            ->defaultSort('overdue_tasks', 'desc')
            ->striped()
            ->paginated([5, 10, 25]);
    }
}
