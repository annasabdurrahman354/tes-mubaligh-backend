@php
    $plugin = filament()->isServing() ? \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::get() : null;
    $heading = $this->getHeading();
    $subheading = $this->getSubheading();
    $filters = $this->getFilters();
    $indicatorsCount = $this->getIndicatorsCount();
    $darkMode = $this->getDarkMode();
    $width = $this->getFilterFormWidth();
    $pollingInterval = $this->getPollingInterval();
    $chartId = $this->getChartId();
    $chartOptions = $this->getOptions();
    $filterFormAccessible = $this->getFilterFormAccessible();
    $loadingIndicator = $this->getLoadingIndicator();
    $contentHeight = $this->getContentHeight();
    $deferLoading = $this->getDeferLoading();
    $footer = $this->getFooter();
    $readyToLoad = $this->readyToLoad;
    $extraJsOptions = $this->extraJsOptions();
@endphp
<x-filament-widgets::widget class="filament-widgets-chart-widget filament-apex-charts-widget">
    <x-filament::section :description="$subheading" :heading="$heading">
        <div>
            @if ($filters)
                <x-slot name="headerEnd">
                    <x-filament::input.wrapper
                        wire:target="filter"
                        class="w-max sm:-my-2"
                    >
                        <x-filament::input.select
                            wire:model.live="filter"
                        >
                            @foreach ($filters as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @if ($filterFormAccessible)
                        <div x-data="{ dropdownOpen: false }" @apexhcharts-dropdown.window="dropdownOpen = $event.detail.open">
                            <x-filament-apex-charts::filter-form :$indicatorsCount :$width>
                                {{ $this->form }}
                            </x-filament-apex-charts::filter-form>
                        </div>
                    @endif
                </x-slot>
            @endif

            <x-filament-apex-charts::chart :$chartId :$chartOptions :$contentHeight :$pollingInterval :$loadingIndicator
                :$darkMode :$deferLoading :$readyToLoad :$extraJsOptions />

            @if ($footer)
                <div class="relative">
                    {!! $footer !!}
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
