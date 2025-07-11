<x-filament-widgets::widget>
    <x-filament::section :heading="__('dashboard.timeline.heading')">
        <div class="space-y-4">
            @if (count($projects) === 0)
                <div class="p-4 text-center text-gray-500">
                    <p class="text-sm">{{ __('dashboard.timeline.no_projects') }}</p>
                    <p class="text-xs">{{ __('dashboard.timeline.check_back') }}</p>
                </div>
            @else
                <div class="space-y-6 mt-4">
                    @foreach ($projects as $project)
                        <div class="space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-lg">{{ $project['name'] }}</h3>
                                    <p class="text-sm font-semibold {{ $project['status_text_color'] }}">
                                        {{ $project['status'] }}
                                    </p>
                                </div>

                                <div class="text-right text-sm text-gray-500">
                                    {{ $project['start_date'] }} - {{ $project['end_date'] }}
                                </div>
                            </div>

                            <div class="relative w-full h-10 bg-gray-200 rounded-lg overflow-hidden">
                                <div class="absolute top-0 left-0 h-full"
                                    style="width: {{ $project['progress_percent'] }}%; background-color: {{ $project['progress_bar_color'] }};">
                                </div>

                                @if ($project['remaining_days'] > 0)
                                    <div class="absolute top-0 h-full bg-gray-300"
                                        style="left: {{ $project['progress_percent'] }}%; width: {{ 100 - $project['progress_percent'] }}%;">
                                    </div>
                                @endif

                                <div class="absolute inset-0 flex items-center px-4 text-sm font-medium">
                                    @if ($project['progress_percent'] > 0)
                                        <span class="mr-2 text-white drop-shadow-sm">
                                            {{ __('dashboard.timeline.days_passed', ['count' => $project['past_days']]) }}
                                        </span>
                                    @endif

                                    @if ($project['remaining_days'] > 0)
                                        <span class="ml-auto text-gray-900">
                                            {{ __('dashboard.timeline.days_remaining', ['count' => $project['remaining_days']]) }}
                                        </span>
                                    @elseif($project['remaining_days'] <= 0 && $project['progress_percent'] < 100)
                                        <span class="ml-auto text-red-700">
                                            {{ __('dashboard.timeline.days_overdue', ['count' => abs($project['remaining_days'])]) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
