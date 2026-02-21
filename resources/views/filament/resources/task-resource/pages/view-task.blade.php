<x-filament-panels::page>
    {{-- Presence & Watchers Bar --}}
    <div wire:poll.15s="trackPresence" class="mb-4 flex flex-wrap items-center justify-between gap-4 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 text-sm">
            @php
                $type = $this->record->type?->value ?? 'task';
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
            <x-filament::icon :icon="$typeIcon" class="w-5 h-5 {{ $typeColor }}" />
            <span class="font-bold text-gray-900 dark:text-white">{{ $this->record->identifier }}</span>
        </div>

        <div class="flex items-center gap-3">
            {{-- Watchers --}}
            @php
                $watcherCount = $this->getWatcherCount();
                $watchers = $this->record->watchers()->limit(5)->get();
            @endphp
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-eye class="w-4 h-4 text-gray-500" />
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-medium">{{ $watcherCount }}</span> {{ Str::plural('watcher', $watcherCount) }}
                </span>
                @if($watchers->isNotEmpty())
                    <div class="flex -space-x-2 ml-1">
                        @foreach($watchers->take(4) as $watcher)
                            <div class="w-6 h-6 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800" title="{{ $watcher->name }}">
                                {{ strtoupper(substr($watcher->name, 0, 1)) }}
                            </div>
                        @endforeach
                        @if($watcherCount > 4)
                            <div class="w-6 h-6 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-medium ring-2 ring-white dark:ring-gray-800">
                                +{{ $watcherCount - 4 }}
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

    <div class="flex gap-6">
        {{-- Main Content --}}
        <div class="flex-1 min-w-0 space-y-6">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('filament.admin.resources.projects.view', $this->record->project_id) }}" class="text-primary-600 hover:underline dark:text-primary-400">
                    {{ $this->record->project?->name }}
                </a>
                @if($this->record->sprint)
                    <span>/</span>
                    <span>{{ $this->record->sprint->name }}</span>
                @endif
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

            {{-- Attachments Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Attachments</h3>
                </div>
                <div class="p-4">
                    @php
                        $attachments = $this->record->getMedia('attachments');
                    @endphp

                    @if($attachments->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            @foreach($attachments as $attachment)
                                <div class="group relative bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                                    @if(str_starts_with($attachment->mime_type, 'image/'))
                                        <img src="{{ $attachment->getUrl() }}" alt="{{ $attachment->name }}" class="w-full h-24 object-cover">
                                    @else
                                        <div class="w-full h-24 flex items-center justify-center">
                                            <x-heroicon-o-document class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <a href="{{ $attachment->getUrl() }}" target="_blank" class="p-1.5 bg-white dark:bg-gray-800 rounded-full">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                                        </a>
                                    </div>
                                    <div class="p-2">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $attachment->name }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <label class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary-500 dark:hover:border-primary-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <x-heroicon-o-cloud-arrow-up class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Drop files here or click to upload</span>
                        <input
                            type="file"
                            class="hidden"
                            multiple
                            wire:model="attachment"
                            x-on:change="$wire.uploadAttachment()"
                        >
                    </label>
                </div>
            </div>

            {{-- Activity & Comments Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Activity</h3>
                </div>
                <div class="p-4">
                    @livewire('task-activity-feed', ['task' => $this->record], key('activity-' . $this->record->id))
                </div>
            </div>
        </div>

        {{-- Details Sidebar --}}
        <div class="w-72 flex-shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm sticky top-4 border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Details</h3>
                </div>
                <div class="p-4 space-y-4">
                    {{ $this->detailsForm }}

                    {{-- Watchers --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $this->watchersForm }}
                    </div>

                    {{-- Read-only info --}}
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Reporter</label>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ strtoupper(substr($this->record->reporter?->name ?? 'U', 0, 1)) }}
                                </div>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $this->record->reporter?->name ?? 'Unknown' }}</span>
                            </div>
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

                        @if($this->record->branch)
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Branch</label>
                                <span class="inline-flex items-center gap-1 text-sm text-gray-900 dark:text-gray-100">
                                    <x-heroicon-o-code-bracket class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                    {{ $this->record->branch }}
                                </span>
                            </div>
                        @endif

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
