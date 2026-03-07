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
    <div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-2 line-clamp-2">
        {{ $record->{static::$recordTitleAttribute} }}
    </div>

    {{-- Sprint label --}}
    @if($record->sprint)
        <div class="mb-2">
            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-600 rounded px-1.5 py-0.5">
                <x-heroicon-o-arrow-path class="w-3 h-3" />
                {{ $record->sprint->name }}
            </span>
        </div>
    @endif

    {{-- Footer with Priority, Story Points, Due Date, Assignee --}}
    <div class="flex items-center justify-between gap-2 mt-1">
        <div class="flex items-center gap-2 min-w-0">
            {{-- Priority --}}
            @php
                $priority = $record->priority?->value ?? 'medium';
                $priorityIcon = match($priority) {
                    'critical' => 'heroicon-s-chevron-double-up',
                    'high' => 'heroicon-s-chevron-up',
                    'medium' => 'heroicon-s-minus',
                    'low' => 'heroicon-s-chevron-down',
                    default => 'heroicon-s-minus',
                };
                $priorityColor = match($priority) {
                    'critical' => 'text-red-600',
                    'high' => 'text-orange-500',
                    'medium' => 'text-yellow-500',
                    'low' => 'text-blue-500',
                    default => 'text-gray-400',
                };
            @endphp
            <x-filament::icon
                :icon="$priorityIcon"
                class="w-4 h-4 flex-shrink-0 {{ $priorityColor }}"
                title="Priority: {{ ucfirst($priority) }}"
            />

            {{-- Story Points --}}
            @if($record->story_points)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-gray-100 dark:bg-gray-600 rounded-full flex-shrink-0" title="{{ $record->story_points }} {{ Str::plural('story point', $record->story_points) }}">
                    {{ $record->story_points }}
                </span>
            @endif

            {{-- Due Date --}}
            @if($record->due_date)
                @php
                    $statusValue = is_string($record->status) ? $record->status : ($record->status?->value ?? '');
                    $isOverdue = $record->due_date->isPast() && $statusValue !== 'done';
                @endphp
                <span class="text-xs flex-shrink-0 {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-500' }}" title="Due: {{ $record->due_date->format('M d, Y') }}">
                    {{ $record->due_date->format('M d') }}
                </span>
            @endif
        </div>

        {{-- Assignee Avatar + Name --}}
        <div class="flex items-center gap-1.5 min-w-0 flex-shrink-0">
            @if($record->assignee)
                <div class="w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium flex-shrink-0" title="{{ $record->assignee->name }}">
                    {{ strtoupper(substr($record->assignee->name, 0, 1)) }}
                </div>
                <span class="text-xs text-gray-600 dark:text-gray-300 truncate max-w-[80px]">{{ $record->assignee->name }}</span>
            @else
                <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0" title="Unassigned">
                    <x-filament::icon icon="heroicon-o-user" class="w-3 h-3 text-gray-400" />
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">Unassigned</span>
            @endif
        </div>
    </div>
</div>
