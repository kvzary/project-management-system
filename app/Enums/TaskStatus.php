<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasLabel
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case DONE = 'done';

    /**
     * Get all available values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::IN_REVIEW => 'In Review',
            self::DONE => 'Done',
        };
    }

    /**
     * Legacy label method alias.
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Get the title for Kanban board.
     */
    public function getTitle(): string
    {
        return $this->getLabel();
    }

    /**
     * Get the color for Filament.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::TODO => 'gray',
            self::IN_PROGRESS => 'info',
            self::IN_REVIEW => 'warning',
            self::DONE => 'success',
        };
    }

    /**
     * Legacy colour method alias.
     */
    public function colour(): string
    {
        return $this->getColor();
    }

    /**
     * Determine if the status represents a completed task.
     */
    public function isCompleted(): bool
    {
        return $this === self::DONE;
    }

    /**
     * Get the next logical status in the workflow.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::TODO => self::IN_PROGRESS,
            self::IN_PROGRESS => self::IN_REVIEW,
            self::IN_REVIEW => self::DONE,
            self::DONE => null,
        };
    }
}
