<div class="space-y-4" wire:poll.5s="refreshFeed">
    {{-- Add Comment Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <form wire:submit="addComment">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-medium">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
                <div class="flex-1">
                    <textarea
                        wire:model="newComment"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 resize-none"
                        placeholder="Add a comment..."
                    ></textarea>
                    @error('newComment')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end mt-2">
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="addComment">Comment</span>
                    <span wire:loading wire:target="addComment">Posting...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Activity Feed --}}
    <div class="space-y-3">
        @forelse($feed as $item)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700" wire:key="{{ $item['id'] }}">
                <div class="flex gap-3">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        @if($item['user'])
                            <div class="w-8 h-8 rounded-full {{ $item['type'] === 'comment' ? 'bg-primary-500' : 'bg-gray-400' }} flex items-center justify-center text-white text-sm font-medium">
                                {{ strtoupper(substr($item['user']->name, 0, 1)) }}
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <x-heroicon-o-cog-6-tooth class="w-4 h-4 text-gray-500" />
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $item['user']?->name ?? 'System' }}
                                </span>

                                @if($item['type'] === 'activity')
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">
                                        {{ $item['event'] }}
                                    </span>
                                @endif

                                <span class="text-gray-400 dark:text-gray-500 text-xs">
                                    {{ $item['created_at']->diffForHumans() }}
                                </span>
                            </div>

                            @if($item['type'] === 'comment' && $item['can_delete'])
                                <button
                                    wire:click="deleteComment({{ $item['id'] }})"
                                    wire:confirm="Are you sure you want to delete this comment?"
                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                >
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            @endif
                        </div>

                        @if($item['type'] === 'comment')
                            {{-- Comment Content --}}
                            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert max-w-none">
                                {!! $item['content'] !!}
                            </div>
                        @else
                            {{-- Activity Changes --}}
                            @if(!empty($item['changes']))
                                <div class="mt-2 space-y-1">
                                    @foreach($item['changes'] as $change)
                                        <div class="text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $change['field'] }}:</span>
                                            @if($change['old'])
                                                <span class="text-gray-400 line-through">{{ $change['old'] }}</span>
                                                <x-heroicon-m-arrow-right class="w-3 h-3 inline text-gray-400" />
                                            @endif
                                            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $change['new'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No activity yet. Be the first to comment!</p>
            </div>
        @endforelse
    </div>
</div>
