<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Filament\Widgets\Concerns\HasDepartmentScope;
use App\Models\Project;
use App\Models\WorkflowStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProjectProgressWidget extends BaseWidget
{
    use HasDepartmentScope;

    protected static ?string $heading = 'Project Progress';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $completedSlugs = WorkflowStatus::where('is_completed', true)->pluck('slug')->toArray();
        $departmentIds = $this->getDepartmentIds();

        return $table
            ->query(
                $this->scopeProjectsToDepartments(
                    Project::query()->where('status', ProjectStatus::ACTIVE),
                    $departmentIds
                )
                    ->withCount([
                        'tasks as total_tasks',
                        'tasks as completed_tasks' => fn (Builder $q) => $q->whereIn('status', $completedSlugs),
                        'tasks as overdue_tasks' => fn (Builder $q) => $q->whereNotNull('due_date')
                            ->where('due_date', '<', now())
                            ->whereNotIn('status', $completedSlugs),
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_tasks')
                    ->label('Total Tasks')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('completed_tasks')
                    ->label('Completed')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('overdue_tasks')
                    ->label('Overdue')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->total_tasks > 0
                        ? round(($record->completed_tasks / $record->total_tasks) * 100) . '%'
                        : '0%')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->total_tasks === 0 => 'gray',
                        ($record->completed_tasks / max($record->total_tasks, 1)) >= 0.7 => 'success',
                        ($record->completed_tasks / max($record->total_tasks, 1)) >= 0.4 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => $record->status->colour()),
            ])
            ->defaultSort('overdue_tasks', 'desc')
            ->striped()
            ->paginated([5, 10]);
    }
}
