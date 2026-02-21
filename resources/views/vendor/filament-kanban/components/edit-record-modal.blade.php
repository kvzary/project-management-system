<x-filament-panels::form wire:submit.prevent="editModalFormSubmitted">
    <x-filament::modal id="kanban--edit-record-modal" :slideOver="$this->getEditModalSlideOver()" :width="$this->getEditModalWidth()">
        <x-slot name="header">
            <div class="flex flex-col gap-1">
                {{-- Task Identifier --}}
                @if(isset($this->editModalRecordId) && $this->editModalRecordId)
                    @php
                        $task = \App\Models\Task::with('project')->find($this->editModalRecordId);
                    @endphp
                    @if($task)
                        <div class="flex items-center gap-2 text-sm">
                            @php
                                $type = $task->type?->value ?? 'task';
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
                                class="w-5 h-5 {{ $typeColor }}"
                            />
                            <span class="font-medium text-primary-600 dark:text-primary-400">
                                {{ $task->project?->key ?? 'PROJ' }}-{{ $task->id }}
                            </span>
                        </div>
                    @endif
                @endif
                <x-filament::modal.heading>
                    {{ $this->getEditModalTitle() }}
                </x-filament::modal.heading>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{ $this->form }}

            {{-- Activity Section --}}
            @if(isset($this->editModalRecordId) && $this->editModalRecordId)
                @php
                    $task = \App\Models\Task::find($this->editModalRecordId);
                    $activities = $task ? \Spatie\Activitylog\Models\Activity::where('subject_type', \App\Models\Task::class)
                        ->where('subject_id', $this->editModalRecordId)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get() : collect();
                @endphp

                @if($activities->isNotEmpty())
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 text-gray-400" />
                            Recent Activity
                        </h4>
                        <div class="space-y-3">
                            @foreach($activities as $activity)
                                <div class="flex gap-3 text-sm">
                                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                        <x-filament::icon icon="heroicon-o-pencil" class="w-3 h-3 text-gray-500" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-gray-600 dark:text-gray-300">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $activity->causer?->name ?? 'System' }}
                                            </span>
                                            {{ $activity->description }}
                                            @if($activity->properties->has('attributes'))
                                                @foreach($activity->properties['attributes'] as $key => $value)
                                                    @if(!is_array($value))
                                                        <span class="text-gray-500">{{ str_replace('_', ' ', $key) }}: {{ $value }}</span>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Task Metadata --}}
                @if($task)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Created</span>
                                <p class="text-gray-900 dark:text-gray-100">{{ $task->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Updated</span>
                                <p class="text-gray-900 dark:text-gray-100">{{ $task->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                            @if($task->reporter)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Reporter</span>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $task->reporter->name }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex justify-between w-full">
                <div>
                    @if(isset($this->editModalRecordId) && $this->editModalRecordId)
                        <x-filament::button
                            color="danger"
                            outlined
                            x-on:click="
                                if (confirm('Are you sure you want to delete this task?')) {
                                    $wire.call('deleteRecord', {{ $this->editModalRecordId }});
                                    isOpen = false;
                                }
                            "
                        >
                            Delete
                        </x-filament::button>
                    @endif
                </div>
                <div class="flex gap-3">
                    <x-filament::button color="gray" x-on:click="isOpen = false">
                        {{ $this->getEditModalCancelButtonLabel() }}
                    </x-filament::button>
                    <x-filament::button type="submit">
                        {{ $this->getEditModalSaveButtonLabel() }}
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::form>
