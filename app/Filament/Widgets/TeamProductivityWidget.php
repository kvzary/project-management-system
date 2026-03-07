<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\TeamWorkMonitor;
use App\Filament\Widgets\Concerns\HasDepartmentScope;
use App\Models\User;
use App\Models\WorkflowStatus;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TeamProductivityWidget extends BaseWidget
{
    use HasDepartmentScope;

    protected static ?string $heading = 'Team Productivity';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();
        $departmentIds = $this->getDepartmentIds();

        return $table
            ->query(
                User::query()
                    ->whereHas('assignedTasks')
                    ->when(! empty($departmentIds), fn ($q) => $q->whereHas('departments', fn ($dq) => $dq->whereIn('departments.id', $departmentIds)))
                    ->withCount([
                        'assignedTasks as total_tasks',
                        'assignedTasks as completed_tasks' => fn (Builder $q) => $q->whereIn('status', $completedSlugs),
                        'assignedTasks as overdue_tasks' => fn (Builder $q) => $q->whereNotNull('due_date')
                            ->where('due_date', '<', now())
                            ->whereNotIn('status', $completedSlugs),
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
                TextColumn::make('overdue_tasks')
                    ->label('Overdue')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('completion_rate')
                    ->label('Rate')
                    ->getStateUsing(fn ($record) => $record->total_tasks > 0
                        ? round(($record->completed_tasks / $record->total_tasks) * 100).'%'
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
