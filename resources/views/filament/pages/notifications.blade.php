<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900">
                        <x-heroicon-o-bell class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Unread</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ \Illuminate\Notifications\DatabaseNotification::query()
                                ->where('notifiable_type', \App\Models\User::class)
                                ->where('notifiable_id', auth()->id())
                                ->where('data->format', 'filament')
                                ->whereNull('read_at')
                                ->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Read</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ \Illuminate\Notifications\DatabaseNotification::query()
                                ->where('notifiable_type', \App\Models\User::class)
                                ->where('notifiable_id', auth()->id())
                                ->where('data->format', 'filament')
                                ->whereNotNull('read_at')
                                ->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800">
                        <x-heroicon-o-inbox-stack class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ \Illuminate\Notifications\DatabaseNotification::query()
                                ->where('notifiable_type', \App\Models\User::class)
                                ->where('notifiable_id', auth()->id())
                                ->where('data->format', 'filament')
                                ->count() }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Notifications Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
