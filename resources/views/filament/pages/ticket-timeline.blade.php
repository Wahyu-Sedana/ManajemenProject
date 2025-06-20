<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Project Filter -->
        <div class="mb-6">
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $selectedProject ? $selectedProject->name : 'Select Project' }}
                    </h2>
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="projectId">
                                <option value="">Select Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </x-filament::section>
        </div>

        @if ($selectedProject)
            <!-- Timeline Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-calendar class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Project Timeline</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Timeline view showing ticket duration from start to due date
                    </p>

                    <!-- Legend -->
                    <div class="flex gap-4 text-sm text-gray-600 dark:text-gray-400 mt-4">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 bg-red-500 rounded-sm border border-white"></div>
                            <span>Overdue</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 bg-green-500 rounded-sm border border-white"></div>
                            <span>On Time</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline Table -->
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600"
                                    style="width: 50%;">
                                    Ticket
                                </th>
                                @foreach ($this->getMonthHeaders() as $month)
                                    <th class="px-2 py-3 bg-gray-50 dark:bg-gray-700 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600"
                                        style="width: {{ 50 / count($this->getMonthHeaders()) }}%;">
                                        {{ $month }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($this->getTimelineData()['tasks'] as $task)
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-600">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $task['title'] }}</div>
                                            <div
                                                class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <span class="font-medium">{{ $task['ticket_id'] }}</span>
                                                <span class="text-gray-400 dark:text-gray-500">|</span>
                                                <span>Due: {{ $task['end_date'] }}</span>
                                            </div>
                                            <div class="mt-1">
                                                @if ($task['is_overdue'])
                                                    <span class="text-xs text-red-600 font-semibold">Overdue</span>
                                                @elseif($task['remaining_days'] <= 3)
                                                    <span class="text-xs text-yellow-600 font-medium">Due Soon</span>
                                                @else
                                                    <span class="text-xs text-green-600">On Track</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    @foreach ($this->getMonthHeaders() as $monthIndex => $monthLabel)
                                        <td class="p-0 h-14 relative border-r border-gray-200 dark:border-gray-600">
                                            @if (isset($task['bar_spans'][$monthIndex]))
                                                @php
                                                    $span = $task['bar_spans'][$monthIndex];
                                                    $left = $span['start_position'];
                                                    $width = $span['width_percentage'];
                                                    $backgroundColor = $task['color'];
                                                    $borderStyle = '';
                                                    $opacity = '1';

                                                    if ($task['is_overdue']) {
                                                        $borderStyle = 'border: 2px dashed rgba(255,255,255,0.7);';
                                                        $opacity = '0.8';
                                                    } elseif ($task['remaining_days'] <= 3) {
                                                        $borderStyle = 'border: 2px solid rgba(255,255,255,0.7);';
                                                    }

                                                    $daysText = $task['is_overdue']
                                                        ? 'Overdue'
                                                        : $task['remaining_days'] . 'd';
                                                    $showText = $width >= 20;
                                                @endphp

                                                <div class="absolute top-0 h-full flex items-center justify-center text-xs font-medium text-white rounded-sm overflow-hidden whitespace-nowrap cursor-default"
                                                    style="background-color: {{ $backgroundColor }}; left: {{ $left }}%; width: {{ $width }}%; {{ $borderStyle }} opacity: {{ $opacity }};"
                                                    title="{{ $task['title'] }} - Due: {{ $task['end_date'] }}">
                                                    @if ($showText)
                                                        {{ $daysText }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            @if (count($this->getTimelineData()['tasks']) === 0)
                                <tr>
                                    <td colspan="{{ count($this->getMonthHeaders()) + 1 }}"
                                        class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center justify-center mb-3">
                                            <x-heroicon-o-calendar
                                                class="w-8 h-8 text-gray-400 dark:text-gray-500 mb-2" />
                                            <span>No tickets found with due dates</span>
                                            <span class="text-xs mt-1">Select a different project or add tickets with
                                                due dates</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400 gap-4">
                <div class="flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 p-6">
                    <x-heroicon-o-calendar class="w-16 h-16 text-gray-400 dark:text-gray-500" />
                </div>
                <h2 class="text-xl font-medium text-gray-600 dark:text-gray-300">Please select a project first</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Select a project from the dropdown above to view the timeline
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
