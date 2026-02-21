@props(['status'])

@php
    $borderColor = $status['border_color'] ?? 'border-t-gray-400';
@endphp

<div class="md:w-80 flex-shrink-0 mb-5 md:min-h-full flex flex-col">
    @include(static::$headerView)

    <div
        data-status-id="{{ $status['id'] }}"
        class="flex flex-col flex-1 gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 border-t-2 {{ $borderColor }} min-h-[200px]"
    >
        @forelse($status['records'] as $record)
            @include(static::$recordView)
        @empty
            <div class="flex-1 flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm py-8">
                No tasks
            </div>
        @endforelse
    </div>
</div>
