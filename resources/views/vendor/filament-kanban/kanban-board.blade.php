<x-filament-panels::page>
    {{-- Filter Bar --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-4">
            {{-- Project Filter --}}
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Project</label>
                <select
                    wire:model.live="projectFilter"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">All Projects</option>
                    @foreach(\App\Models\Project::pluck('name', 'id') as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sprint Filter --}}
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sprint</label>
                <select
                    wire:model.live="sprintFilter"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">All Sprints</option>
                    @php
                        $sprints = $projectFilter
                            ? \App\Models\Sprint::where('project_id', $projectFilter)->pluck('name', 'id')
                            : \App\Models\Sprint::pluck('name', 'id');
                    @endphp
                    @foreach($sprints as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assignee Filter --}}
            <div class="w-48">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Assignee</label>
                <select
                    wire:model.live="assigneeFilter"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">All Assignees</option>
                    @foreach(\App\Models\User::pluck('name', 'id') as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Clear Filters --}}
            @if($projectFilter || $sprintFilter || $assigneeFilter)
                <div class="flex items-end">
                    <button
                        type="button"
                        wire:click="$set('projectFilter', null); $set('sprintFilter', null); $set('assigneeFilter', null)"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline"
                    >
                        Clear filters
                    </button>
                </div>
            @endif

            {{-- Task Count --}}
            <div class="ml-auto flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-4 h-4" />
                <span>{{ collect($statuses)->sum(fn($s) => count($s['records'])) }} tasks</span>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div x-data wire:ignore.self class="md:flex overflow-x-auto overflow-y-hidden gap-4 pb-4">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless($disableEditModal)
        <x-filament-kanban::edit-record-modal/>
    @endunless
</x-filament-panels::page>
