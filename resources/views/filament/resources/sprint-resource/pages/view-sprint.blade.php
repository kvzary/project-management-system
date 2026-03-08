<x-filament-panels::page>
    @php
        $sprint = $this->record;
        $stats = $this->getStats();
    @endphp

    {{-- Sprint Header Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    @if($sprint->project)
                        <a href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('view', ['record' => $sprint->project]) }}"
                           class="text-primary-600 dark:text-primary-400 hover:underline">
                            {{ $sprint->project->name }}
                        </a>
                        <span>/</span>
                    @endif
                    <span>{{ $sprint->name }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-calendar class="w-4 h-4" />
                        {{ $sprint->start_date->format('M d, Y') }} — {{ $sprint->end_date->format('M d, Y') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        @if($sprint->isCompleted())
                            Completed
                        @elseif($sprint->isOverdue())
                            <span class="text-danger-600 dark:text-danger-400 font-medium">Overdue by {{ (int) ceil($sprint->end_date->floatDiffInDays(now())) }} days</span>
                        @else
                            {{ $sprint->getRemainingDays() }} days remaining
                        @endif
                    </span>
                    <x-filament::badge :color="$sprint->status->colour()">
                        {{ $sprint->status->label() }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span>{{ $stats['completed'] }} / {{ $stats['total'] }} tasks complete ({{ $stats['progress'] }}%)</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div
                    class="h-2.5 rounded-full transition-all duration-500 {{ $stats['progress'] >= 70 ? 'bg-success-500' : ($stats['progress'] >= 40 ? 'bg-warning-500' : 'bg-primary-500') }}"
                    style="width: {{ $stats['progress'] }}%"
                ></div>
            </div>
        </div>

        {{-- Goal --}}
        @if($sprint->goal)
            <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Sprint Goal</p>
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                    {!! $sprint->goal !!}
                </div>
            </div>
        @endif
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Tasks</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $stats['completed'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Done</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $stats['inProgress'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">In Progress</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $stats['todo'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">To Do</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['totalPoints'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Points</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['completedPoints'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Points Done</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Tasks Table --}}
    <x-filament::section>
        <x-slot name="heading">Tasks in Sprint</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
