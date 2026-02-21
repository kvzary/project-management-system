@props([
    'notifications',
    'unreadNotificationsCount',
])

@php
    use Filament\Support\Enums\Alignment;

    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages();
@endphp

<x-filament::modal
    :alignment="$hasNotifications ? null : Alignment::Center"
    close-button
    :description="$hasNotifications ? null : 'You\'re all caught up!'"
    :heading="$hasNotifications ? null : 'No notifications'"
    :icon="$hasNotifications ? null : 'heroicon-o-bell-slash'"
    :icon-alias="$hasNotifications ? null : 'notifications::database.modal.empty-state'"
    :icon-color="$hasNotifications ? null : 'gray'"
    id="database-notifications"
    slide-over
    :sticky-header="$hasNotifications"
    width="md"
>
    @if ($hasNotifications)
        <x-slot name="header">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
                        @if($unreadNotificationsCount > 0)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                {{ $unreadNotificationsCount }} new
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    @if($unreadNotificationsCount > 0)
                        <button
                            wire:click="markAllNotificationsAsRead"
                            type="button"
                            class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                        >
                            Mark all as read
                        </button>
                    @else
                        <span class="text-xs text-gray-400">All caught up</span>
                    @endif

                    <a
                        href="{{ \App\Filament\Pages\Notifications::getUrl() }}"
                        class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                        x-on:click="$dispatch('close-modal', { id: 'database-notifications' })"
                    >
                        View all
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="-mx-6 -mt-6 -mb-6">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->unread();
                    $icon = $data['icon'] ?? 'heroicon-o-bell';
                    $iconColor = $data['iconColor'] ?? 'gray';
                    $title = $data['title'] ?? 'Notification';
                    $body = $data['body'] ?? '';
                    $actions = $data['actions'] ?? [];
                    $viewUrl = $actions[0]['url'] ?? null;
                @endphp
                <div
                    @class([
                        'group relative px-4 py-3 transition-colors',
                        'bg-primary-50/50 dark:bg-primary-950/20' => $isUnread,
                        'hover:bg-gray-50 dark:hover:bg-white/5' => !$isUnread,
                        'border-b border-gray-100 dark:border-gray-800' => !$loop->last,
                    ])
                >
                    {{-- Unread indicator --}}
                    @if($isUnread)
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500"></div>
                    @endif

                    <div class="flex gap-3">
                        {{-- Icon --}}
                        <div @class([
                            'flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center',
                            'bg-primary-100 dark:bg-primary-900/50' => $iconColor === 'primary' || $iconColor === 'info',
                            'bg-success-100 dark:bg-success-900/50' => $iconColor === 'success',
                            'bg-warning-100 dark:bg-warning-900/50' => $iconColor === 'warning',
                            'bg-danger-100 dark:bg-danger-900/50' => $iconColor === 'danger',
                            'bg-gray-100 dark:bg-gray-800' => $iconColor === 'gray' || !$iconColor,
                        ])>
                            <x-filament::icon
                                :icon="$icon"
                                @class([
                                    'w-4 h-4',
                                    'text-primary-600 dark:text-primary-400' => $iconColor === 'primary' || $iconColor === 'info',
                                    'text-success-600 dark:text-success-400' => $iconColor === 'success',
                                    'text-warning-600 dark:text-warning-400' => $iconColor === 'warning',
                                    'text-danger-600 dark:text-danger-400' => $iconColor === 'danger',
                                    'text-gray-500 dark:text-gray-400' => $iconColor === 'gray' || !$iconColor,
                                ])
                            />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p @class([
                                'text-sm leading-tight',
                                'font-medium text-gray-900 dark:text-white' => $isUnread,
                                'text-gray-700 dark:text-gray-300' => !$isUnread,
                            ])>
                                {{ $title }}
                            </p>
                            @if($body)
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $body }}
                                </p>
                            @endif
                            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex-shrink-0 flex items-start gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            @if($viewUrl)
                                <a
                                    href="{{ $viewUrl }}"
                                    wire:click="markNotificationAsRead('{{ $notification->id }}')"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-950 transition-colors"
                                    title="View"
                                >
                                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                </a>
                            @endif
                            @if($isUnread)
                                <button
                                    wire:click="markNotificationAsRead('{{ $notification->id }}')"
                                    type="button"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-success-600 hover:bg-success-50 dark:hover:text-success-400 dark:hover:bg-success-950 transition-colors"
                                    title="Mark as read"
                                >
                                    <x-heroicon-o-check class="w-4 h-4" />
                                </button>
                            @endif
                            <button
                                wire:click="removeNotification('{{ $notification->id }}')"
                                type="button"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-danger-600 hover:bg-danger-50 dark:hover:text-danger-400 dark:hover:bg-danger-950 transition-colors"
                                title="Dismiss"
                            >
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($isPaginated)
            <x-slot name="footer">
                <x-filament::pagination :paginator="$notifications" />
            </x-slot>
        @endif
    @else
        <x-slot name="footer">
            <div class="text-center py-2">
                <a
                    href="{{ \App\Filament\Pages\Notifications::getUrl() }}"
                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    View notification history
                </a>
            </div>
        </x-slot>
    @endif
</x-filament::modal>
