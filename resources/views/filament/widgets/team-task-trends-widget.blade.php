@php
    use Filament\Support\Facades\FilamentView;

    $heading = $this->getHeading();
    $filters = $this->getFilters();
    $assigneeOptions = $this->getAssigneeOptions();
    $statusOptions = $this->getStatusOptions();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :heading="$heading">
        <x-slot name="headerEnd">
            <div class="flex items-center gap-2 sm:-my-2">

                {{-- Assignee Filter --}}
                <x-filament::input.wrapper inline-prefix wire:target="assigneeFilter" class="w-max">
                    <x-filament::input.select inline-prefix wire:model.live="assigneeFilter">
                        @foreach ($assigneeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                {{-- Status Filter --}}
                <x-filament::input.wrapper inline-prefix wire:target="statusFilter" class="w-max">
                    <x-filament::input.select inline-prefix wire:model.live="statusFilter">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                {{-- Year Filter --}}
                @if ($filters)
                    <x-filament::input.wrapper inline-prefix wire:target="filter" class="w-max">
                        <x-filament::input.select inline-prefix wire:model.live="filter">
                            @foreach ($filters as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif

            </div>
        </x-slot>

        <div>
            <div
                @if (FilamentView::hasSpaMode())
                    ax-load="visible"
                @else
                    ax-load
                @endif
                ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($this->getType()),
                })"
                x-ignore
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span x-ref="backgroundColorElement" class="text-gray-100 dark:text-gray-800"></span>
                <span x-ref="borderColorElement" class="text-gray-400"></span>
                <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
