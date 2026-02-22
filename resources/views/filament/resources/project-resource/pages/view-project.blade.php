<x-filament-panels::page>
    {{-- Presence & Members Bar --}}
    <div wire:poll.15s="trackPresence" class="mb-4 flex flex-col gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-4">
        <div class="flex items-center gap-3 text-sm">
            <x-filament::icon icon="heroicon-o-briefcase" class="w-5 h-5 text-primary-500" />
            <span class="font-bold text-gray-900 dark:text-white">{{ $this->record->key }}</span>
        </div>

        <div class="flex items-center gap-3">
            {{-- Team Members --}}
            @php
                $memberCount = $this->getMemberCount();
                $members = $this->record->members()->limit(5)->get();
            @endphp
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-users class="w-4 h-4 text-gray-500" />
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-medium">{{ $memberCount }}</span> {{ Str::plural('member', $memberCount) }}
                </span>
                @if($members->isNotEmpty())
                    <div class="flex -space-x-2 ml-1">
                        @foreach($members->take(4) as $member)
                            <div class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800" title="{{ $member->name }}">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                        @endforeach
                        @if($memberCount > 4)
                            <div class="w-6 h-6 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800">
                                +{{ $memberCount - 4 }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Live Viewers --}}
            @php
                $viewerCount = $this->getViewerCount();
                $viewers = $this->getViewers();
            @endphp
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $viewerCount > 0 ? 'bg-success-100 dark:bg-success-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
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
                        <span class="font-medium">{{ $viewerCount }}</span> {{ Str::plural('other', $viewerCount) }} viewing
                    @else
                        Only you
                    @endif
                </span>
                @if($viewers->isNotEmpty())
                    <div class="flex -space-x-2 ml-1">
                        @foreach($viewers->take(3) as $viewer)
                            <div class="w-6 h-6 rounded-full bg-success-500 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800" title="{{ $viewer->name }} is viewing">
                                {{ strtoupper(substr($viewer->name, 0, 1)) }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row">
        {{-- Main Content --}}
        <div class="flex-1 min-w-0 space-y-6">
            {{-- Project Stats --}}
            @php
                $workflow = $this->record->getWorkflow();
                $workflowStatuses = $workflow ? $workflow->statuses : collect();
                $totalTasks = $this->record->tasks()->count();
                $statColorMap = [
                    'gray' => 'text-gray-500 dark:text-gray-400',
                    'info' => 'text-blue-600 dark:text-blue-400',
                    'success' => 'text-green-600 dark:text-green-400',
                    'warning' => 'text-amber-600 dark:text-amber-400',
                    'danger' => 'text-red-600 dark:text-red-400',
                    'primary' => 'text-sky-600 dark:text-sky-400',
                ];
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ min($workflowStatuses->count() + 1, 6) }} gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $totalTasks }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Tasks</div>
                </div>
                @foreach($workflowStatuses as $ws)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <div class="text-2xl font-bold {{ $statColorMap[$ws->color] ?? 'text-gray-500 dark:text-gray-400' }}">
                            {{ $this->record->tasks()->where('status', $ws->slug)->count() }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $ws->name }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Description Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Description</h3>
                    @if(!$this->isEditingDescription)
                        <button
                            wire:click="startEditingDescription"
                            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            Edit
                        </button>
                    @endif
                </div>
                <div class="p-4">
                    @if($this->isEditingDescription)
                        <div class="space-y-3">
                            {{ $this->descriptionForm }}
                            <div class="flex gap-2 justify-end">
                                <x-filament::button color="gray" size="sm" wire:click="cancelEditingDescription">
                                    Cancel
                                </x-filament::button>
                                <x-filament::button size="sm" wire:click="saveDescription">
                                    Save
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        <div
                            wire:click="startEditingDescription"
                            class="prose prose-sm dark:prose-invert max-w-none cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg p-2 -m-2 transition-colors min-h-[60px]"
                            title="Click to edit"
                        >
                            @if($this->record->description)
                                {!! $this->record->description !!}
                            @else
                                <p class="text-gray-400 dark:text-gray-500 italic">Click to add a description...</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tasks List (Epic View) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tasks in this Epic</h3>
                    <a
                        href="{{ route('filament.admin.resources.tasks.create', ['project_id' => $this->record->id]) }}"
                        class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        + Add Task
                    </a>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $tasks = $this->record->tasks()->with(['assignee', 'sprint'])->orderBy('position')->orderBy('created_at', 'desc')->get();
                    @endphp

                    @forelse($tasks as $task)
                        @php
                            $badgeColorMap = [
                                'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                'success' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                'primary' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300',
                            ];
                            $typeIcons = [
                                'bug' => 'heroicon-o-bug-ant',
                                'story' => 'heroicon-o-bookmark',
                                'epic' => 'heroicon-o-bolt',
                                'subtask' => 'heroicon-o-minus',
                                'task' => 'heroicon-o-check-circle',
                            ];
                            $typeColors = [
                                'bug' => 'text-red-500',
                                'story' => 'text-green-500',
                                'epic' => 'text-purple-500',
                                'subtask' => 'text-cyan-500',
                                'task' => 'text-blue-500',
                            ];
                            $priorityColors = [
                                'low' => 'text-blue-400',
                                'medium' => 'text-yellow-500',
                                'high' => 'text-orange-500',
                                'critical' => 'text-red-500',
                            ];
                        @endphp
                        <a
                            href="{{ route('filament.admin.resources.tasks.view', $task) }}"
                            class="flex flex-wrap items-center gap-2 sm:gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            {{-- Type Icon --}}
                            <x-filament::icon
                                :icon="$typeIcons[$task->type?->value ?? 'task'] ?? 'heroicon-o-check-circle'"
                                class="w-5 h-5 {{ $typeColors[$task->type?->value ?? 'task'] ?? 'text-blue-500' }}"
                            />

                            {{-- Task Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $task->identifier }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $task->title }}</span>
                                </div>
                                @if($task->sprint)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $task->sprint->name }}</span>
                                @endif
                            </div>

                            {{-- Priority --}}
                            @if($task->priority)
                                <x-heroicon-s-chevron-up class="w-4 h-4 {{ $priorityColors[$task->priority->value] ?? 'text-gray-400' }}" />
                            @endif

                            {{-- Status Badge --}}
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColorMap[$task->status_color] ?? $badgeColorMap['gray'] }}">
                                {{ $task->status_label }}
                            </span>

                            {{-- Assignee --}}
                            @if($task->assignee)
                                <div class="flex items-center gap-2" title="{{ $task->assignee->name }}">
                                    <div class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium">
                                        {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                    </div>
                                </div>
                            @else
                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center" title="Unassigned">
                                    <x-heroicon-o-user class="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">No tasks yet</p>
                            <a
                                href="{{ route('filament.admin.resources.tasks.create', ['project_id' => $this->record->id]) }}"
                                class="inline-flex items-center gap-1 mt-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
                            >
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Create the first task
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Quick Links</h3>
                </div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    <a
                        href="{{ route('filament.admin.pages.tasks-kanban-board', ['projectFilter' => $this->record->id]) }}"
                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600"
                    >
                        <x-heroicon-o-view-columns class="w-5 h-5 text-primary-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Kanban Board</span>
                    </a>
                    <a
                        href="{{ route('filament.admin.resources.tasks.index', ['tableFilters[project][value]' => $this->record->id]) }}"
                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600"
                    >
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">All Tasks</span>
                    </a>
                    <a
                        href="{{ route('filament.admin.resources.sprints.index', ['tableFilters[project][value]' => $this->record->id]) }}"
                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600"
                    >
                        <x-heroicon-o-arrow-path class="w-5 h-5 text-primary-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Sprints</span>
                    </a>
                    @if($this->record->pull_request_url)
                        <a
                            href="{{ $this->record->pull_request_url }}"
                            target="_blank"
                            class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600"
                        >
                            <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5 text-primary-500" />
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Pull Request</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Activity & Comments Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Activity</h3>
                </div>
                <div class="p-4">
                    @livewire('project-activity-feed', ['project' => $this->record], key('activity-' . $this->record->id))
                </div>
            </div>
        </div>

        {{-- Details Sidebar --}}
        <div class="w-full lg:w-72 lg:flex-shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm sticky top-4 border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Details</h3>
                </div>
                <div class="p-4 space-y-4">
                    {{ $this->detailsForm }}

                    {{-- Read-only info --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Project Key</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-300">
                                {{ $this->record->key }}
                            </span>
                        </div>

                        @if($this->record->creators->count() > 0)
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Creators</label>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($this->record->creators as $creator)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">
                                            {{ $creator->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Team Members</label>
                            @if($this->record->members->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($this->record->members as $member)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">
                                            {{ $member->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">No members</span>
                            @endif
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Sprints</label>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->sprints()->count() }} sprints</span>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Created</label>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->created_at->format('M d, Y H:i') }}</span>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Updated</label>
                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
