<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <x-filament::section>
            <x-slot name="heading">Filter Options</x-slot>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium text-gray-950 dark:text-white mb-2">
                        Team Member
                    </label>
                    <select
                        wire:model.live="selectedUserId"
                        class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">All Team Members</option>
                        @foreach($this->getUsers() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium text-gray-950 dark:text-white mb-2">
                        From Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium text-gray-950 dark:text-white mb-2">
                        To Date
                    </label>
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>
            </div>
        </x-filament::section>

        @php
            $stats = $this->getStats();
            $user = $this->getSelectedUser();
            $tasksByStatus = $this->getTasksByStatus();
            $weeklyProgress = $this->getWeeklyProgress();
        @endphp

        {{-- Selected User Header --}}
        @if($user)
            <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-center w-16 h-16 text-2xl font-bold text-white rounded-full bg-primary-500">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                </div>
            </div>
        @else
            <x-filament::section>
                <div class="text-center py-4">
                    <x-heroicon-o-users class="w-12 h-12 mx-auto text-gray-400 mb-2" />
                    <p class="text-gray-500 dark:text-gray-400">Select a team member to view their detailed work progress, or view all team stats below.</p>
                </div>
            </x-filament::section>
        @endif

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Total Tasks --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900">
                        <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_tasks'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Completed --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['completed_tasks'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- In Progress --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-info-100 dark:bg-info-900">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-info-600 dark:text-info-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">In Progress</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['in_progress_tasks'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Overdue --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-danger-100 dark:bg-danger-900">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Overdue</p>
                        <p class="text-2xl font-bold {{ $stats['overdue_tasks'] > 0 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">{{ $stats['overdue_tasks'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Second Row Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Completion Rate --}}
            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Completion Rate</p>
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-20 h-20 transform -rotate-90">
                            <circle cx="40" cy="40" r="35" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700" />
                            <circle cx="40" cy="40" r="35" stroke="currentColor" stroke-width="6" fill="none"
                                class="{{ $stats['completion_rate'] >= 70 ? 'text-success-500' : ($stats['completion_rate'] >= 40 ? 'text-warning-500' : 'text-danger-500') }}"
                                stroke-dasharray="{{ $stats['completion_rate'] * 2.2 }} 220"
                                stroke-linecap="round" />
                        </svg>
                        <span class="absolute text-lg font-bold text-gray-900 dark:text-white">{{ $stats['completion_rate'] }}%</span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Completed in Period --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-calendar-days class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Completed (Period)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['tasks_completed_in_period'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Story Points Total --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                        <x-heroicon-o-star class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Points</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_story_points'] }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Story Points Completed --}}
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                        <x-heroicon-s-star class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Points Done</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['completed_story_points'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Task Distribution & Weekly Progress --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Task Distribution by Status --}}
            <x-filament::section>
                <x-slot name="heading">Task Distribution</x-slot>
                <div class="space-y-3">
                    @php
                        $statusLabels = [
                            'todo' => ['label' => 'To Do', 'color' => 'bg-gray-500'],
                            'in_progress' => ['label' => 'In Progress', 'color' => 'bg-info-500'],
                            'in_review' => ['label' => 'In Review', 'color' => 'bg-warning-500'],
                            'done' => ['label' => 'Done', 'color' => 'bg-success-500'],
                            'completed' => ['label' => 'Completed', 'color' => 'bg-success-500'],
                        ];
                        $totalForBar = array_sum($tasksByStatus) ?: 1;
                    @endphp
                    @forelse($tasksByStatus as $status => $count)
                        @php
                            $info = $statusLabels[$status] ?? ['label' => ucwords(str_replace('_', ' ', $status)), 'color' => 'bg-gray-400'];
                            $percentage = round(($count / $totalForBar) * 100, 1);
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-300">{{ $info['label'] }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="{{ $info['color'] }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No tasks found</p>
                    @endforelse
                </div>
            </x-filament::section>

            {{-- Weekly Progress --}}
            <x-filament::section>
                <x-slot name="heading">Weekly Completed Tasks</x-slot>
                <div class="space-y-2">
                    @php
                        $maxCompleted = max(array_column($weeklyProgress, 'completed')) ?: 1;
                    @endphp
                    @forelse($weeklyProgress as $week)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-16">{{ $week['week'] }}</span>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="bg-primary-500 h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                     style="width: {{ ($week['completed'] / $maxCompleted) * 100 }}%; min-width: {{ $week['completed'] > 0 ? '2rem' : '0' }}">
                                    @if($week['completed'] > 0)
                                        <span class="text-xs text-white font-medium">{{ $week['completed'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No data for selected period</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        {{-- Tasks Table --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ $user ? $user->name . "'s Tasks" : 'All Team Tasks' }}
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
