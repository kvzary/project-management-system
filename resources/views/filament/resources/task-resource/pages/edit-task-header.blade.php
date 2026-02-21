<div
    wire:poll.15s="trackPresence"
    class="mb-4"
>
    <div class="flex flex-wrap items-center gap-4">
        {{-- Task Identifier --}}
        <div class="flex items-center gap-2">
            <span class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $record->identifier }}
            </span>
        </div>

        <div class="flex-1"></div>

        {{-- Watchers --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800">
            <x-heroicon-o-eye class="w-4 h-4 text-gray-500 dark:text-gray-400" />
            <span class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-medium">{{ $watcherCount }}</span> {{ Str::plural('watcher', $watcherCount) }}
            </span>
            @if($watchers->isNotEmpty())
                <div class="flex -space-x-2 ml-1">
                    @foreach($watchers->take(5) as $watcher)
                        <div
                            class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800"
                            title="{{ $watcher->name }}"
                        >
                            {{ strtoupper(substr($watcher->name, 0, 1)) }}
                        </div>
                    @endforeach
                    @if($watcherCount > 5)
                        <div class="w-6 h-6 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800">
                            +{{ $watcherCount - 5 }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Live Viewers --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $viewerCount > 0 ? 'bg-success-100 dark:bg-success-900/30' : 'bg-gray-100 dark:bg-gray-800' }}">
            <span class="relative flex h-2 w-2">
                @if($viewerCount > 0)
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-400"></span>
                @endif
            </span>
            <span class="text-sm {{ $viewerCount > 0 ? 'text-success-700 dark:text-success-300' : 'text-gray-600 dark:text-gray-300' }}">
                @if($viewerCount > 0)
                    <span class="font-medium">{{ $viewerCount }}</span> {{ Str::plural('person', $viewerCount) }} viewing
                @else
                    Only you viewing
                @endif
            </span>
            @if($viewers->isNotEmpty())
                <div class="flex -space-x-2 ml-1">
                    @foreach($viewers->take(3) as $viewer)
                        <div
                            class="w-6 h-6 rounded-full bg-success-500 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800"
                            title="{{ $viewer->name }} is viewing"
                        >
                            {{ strtoupper(substr($viewer->name, 0, 1)) }}
                        </div>
                    @endforeach
                    @if($viewerCount > 3)
                        <div class="w-6 h-6 rounded-full bg-success-400 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800">
                            +{{ $viewerCount - 3 }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
