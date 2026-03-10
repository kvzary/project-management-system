<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Schemas\Schema;
use \Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\DateSelectInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\EventResizeInfo;
use Illuminate\Database\Eloquent\Model;

class TaskCalendarWidget extends CalendarWidget {
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;
    protected ?string $locale = 'en';
    protected bool $dayMaxEvents = false;
    protected string | HtmlString | bool | null $heading = null;
    protected bool $dateClickEnabled = true;
    protected bool $dateSelectEnabled = true;
    protected bool $eventClickEnabled = true;
    protected ?string $defaultEventClickAction = 'edit';
    protected bool $eventResizeEnabled = true;
    protected bool $eventDragEnabled = true;

    protected function getEvents(FetchInfo $info): Collection | array | Builder {
        return Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $info->start)
            ->whereDate('due_date', '<=', $info->end);
    }

    public function getHeading(): string|HtmlString {
        return new HtmlString('<div>Tasks This Month</div>');
    }

    public function createTaskAction(): CreateAction {
        return $this->createAction(Task::class)
            ->mountUsing(function(Schema $form, ?DateClickInfo $dateClickInfo, ?DateSelectInfo $dateSelectInfo) {
                $form->fill([
                    'due_date'  => $dateClickInfo?->date ?? $dateSelectInfo?->end,
                ]);
            });
    }

    protected function onDateClick(DateClickInfo $info): void {
        $this->mountAction('createTask');
    }

    protected function onDateSelect(DateSelectInfo $info): void {
        $this->mountAction('createTask');
    }

    public function onEventResize(EventResizeInfo $info, Model $task): bool {
        $task->update([
            'due_date'  => $info->event->getEnd(),
        ]);

        return true;
    }

    protected function onEventDrop(EventDropInfo $info, Model $task): bool {
        $dueDate = $info->event->getEnd();

        $task->update([
            'due_date'  => $dueDate,
        ]);

        return true;
    }
}
