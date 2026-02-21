@php
    $bgColor = $status['dot_color'] ?? 'bg-gray-500';
    $recordCount = count($status['records'] ?? []);
@endphp
<div class="mb-3 flex items-center justify-between px-2">
    <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full {{ $bgColor }}"></div>
        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 uppercase tracking-wide">
            {{ $status['title'] }}
        </h3>
    </div>
    <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 text-xs font-medium bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-full">
        {{ $recordCount }}
    </span>
</div>
