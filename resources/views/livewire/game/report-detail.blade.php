<div class="max-w-4xl mx-auto p-6">
    <!-- Report Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $report->title }}</h1>
                @if($report->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $report->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $report->type === 'attack' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                       ($report->type === 'defense' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200') }}">
                    {{ ucfirst($report->type) }}
                </span>
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $report->status === 'victory' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                       ($report->status === 'defeat' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                    {{ ucfirst($report->status) }}
                </span>
            </div>
        </div>

        @if($report->content)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Report Details</h3>
                <div class="prose dark:prose-invert max-w-none">
                    {!! nl2br(e($report->content)) !!}
                </div>
            </div>
        @endif

        @if($report->battle_data)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Battle Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(isset($report->battle_data['attacker_losses']))
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                            <h4 class="font-medium text-red-900 dark:text-red-200">Attacker Losses</h4>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($report->battle_data['attacker_losses']) }}</p>
                        </div>
                    @endif
                    @if(isset($report->battle_data['defender_losses']))
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <h4 class="font-medium text-green-900 dark:text-green-200">Defender Losses</h4>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($report->battle_data['defender_losses']) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($report->attacker)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Attacker</h4>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ $report->attacker->name }}</p>
                    @if($report->fromVillage)
                        <p class="text-sm text-gray-600 dark:text-gray-400">From: {{ $report->fromVillage->name }}</p>
                    @endif
                </div>
            @endif
            @if($report->defender)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Defender</h4>
                    <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ $report->defender->name }}</p>
                    @if($report->toVillage)
                        <p class="text-sm text-gray-600 dark:text-gray-400">Village: {{ $report->toVillage->name }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Comments Section -->
    <livewire:game.report-comments :report="$report" />
</div>
