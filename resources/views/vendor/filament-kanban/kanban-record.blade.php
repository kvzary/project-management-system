<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="record bg-white dark:bg-gray-700 rounded-lg px-3 py-3 cursor-grab text-gray-600 dark:text-gray-200 shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-600"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    @endif
>
    {{-- Task Type & Identifier --}}
    <div class="flex items-center gap-2 mb-2">
        @php
            $type = $record->type?->value ?? 'task';
            $typeIcon = match($type) {
                'bug' => 'heroicon-o-bug-ant',
                'story' => 'heroicon-o-bookmark',
                'epic' => 'heroicon-o-bolt',
                'subtask' => 'heroicon-o-minus',
                default => 'heroicon-o-check-circle',
            };
            $typeColor = match($type) {
                'bug' => 'text-red-500',
                'story' => 'text-green-500',
                'epic' => 'text-purple-500',
                'subtask' => 'text-cyan-500',
                default => 'text-blue-500',
            };
        @endphp
        <x-filament::icon
            :icon="$typeIcon"
            class="w-4 h-4 {{ $typeColor }}"
        />
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
            {{ $record->project?->key ?? 'PROJ' }}-{{ $record->getKey() }}
        </span>
    </div>

    {{-- Title --}}
    <div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-3 line-clamp-2">
        {{ $record->{static::$recordTitleAttribute} }}
    </div>

    {{-- Footer with Priority, Story Points, Assignee --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            {{-- Priority --}}
            @php
                $priority = $record->priority?->value ?? 'medium';
                $priorityIcon = match($priority) {
                    'highest' => 'heroicon-s-chevron-double-up',
                    'high' => 'heroicon-s-chevron-up',
                    'medium' => 'heroicon-s-minus',
                    'low' => 'heroicon-s-chevron-down',
                    'lowest' => 'heroicon-s-chevron-double-down',
                    default => 'heroicon-s-minus',
                };
                $priorityColor = match($priority) {
                    'highest' => 'text-red-600',
                    'high' => 'text-orange-500',
                    'medium' => 'text-yellow-500',
                    'low' => 'text-blue-500',
                    'lowest' => 'text-blue-300',
                    default => 'text-gray-400',
                };
            @endphp
            <x-filament::icon
                :icon="$priorityIcon"
                class="w-4 h-4 {{ $priorityColor }}"
                title="Priority: {{ ucfirst($priority) }}"
            />

            {{-- Story Points --}}
            @if($record->story_points)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-gray-100 dark:bg-gray-600 rounded-full" title="Story Points">
                    {{ $record->story_points }}
                </span>
            @endif

            {{-- Due Date --}}
            @if($record->due_date)
                @php
                    $statusValue = is_string($record->status) ? $record->status : ($record->status?->value ?? '');
                    $isOverdue = $record->due_date->isPast() && $statusValue !== 'done';
                @endphp
                <span class="text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-500' }}" title="Due: {{ $record->due_date->format('M d, Y') }}">
                    {{ $record->due_date->format('M d') }}
                </span>
            @endif
        </div>

        {{-- Assignee Avatar --}}
        <div>
            @if($record->assignee)
                <div class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium" title="{{ $record->assignee->name }}">
                    {{ strtoupper(substr($record->assignee->name, 0, 1)) }}
                </div>
            @else
                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center" title="Unassigned">
                    <x-filament::icon
                        icon="heroicon-o-user"
                        class="w-4 h-4 text-gray-400"
                    />
                </div>
            @endif
        </div>
    </div>
</div>
