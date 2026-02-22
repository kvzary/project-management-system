<x-filament-panels::page>
    {{-- Filter Bar --}}
    <div class="p-4 mb-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center">
            {{-- Project Filter --}}
            <div class="w-full sm:w-48">
                <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Project</label>
                <select wire:model.live="projectFilter"
                    class="block w-full text-sm border-gray-300 rounded-lg shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Projects</option>
                    @foreach (\App\Models\Project::pluck('name', 'id') as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sprint Filter --}}
            <div class="w-full sm:w-48">
                <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Sprint</label>
                <select wire:model.live="sprintFilter"
                    class="block w-full text-sm border-gray-300 rounded-lg shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Sprints</option>
                    @php
                        $sprints = $projectFilter
                            ? \App\Models\Sprint::where('project_id', $projectFilter)->pluck('name', 'id')
                            : \App\Models\Sprint::pluck('name', 'id');
                    @endphp
                    @foreach ($sprints as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assignee Filter --}}
            <div class="w-full sm:w-48">
                <label class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Assignee</label>
                <select wire:model.live="assigneeFilter"
                    class="block w-full text-sm border-gray-300 rounded-lg shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Assignees</option>
                    @foreach (\App\Models\User::pluck('name', 'id') as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Clear Filters --}}
            @if ($projectFilter || $sprintFilter || $assigneeFilter)
                <div class="flex items-end">
                    <button type="button"
                        wire:click="$set('projectFilter', null); $set('sprintFilter', null); $set('assigneeFilter', null)"
                        class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Clear filters
                    </button>
                </div>
            @endif

            {{-- Task Count --}}
            <div class="flex items-center gap-2 ml-auto text-sm text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-4 h-4" />
                <span>{{ collect($statuses)->sum(fn($s) => count($s['records'])) }} tasks</span>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div x-data wire:ignore.self class="flex flex-col gap-4 pb-4 md:flex-row md:overflow-x-auto md:overflow-y-hidden">
        @foreach ($statuses as $status)
            <div class="w-full md:w-80 md:flex-shrink-0">
                @include(static::$statusView)
            </div>
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>

    @unless ($disableEditModal)
        <x-filament-kanban::edit-record-modal />
    @endunless
</x-filament-panels::page>
