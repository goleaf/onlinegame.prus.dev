<div class="max-w-4xl mx-auto p-6">
    <!-- Battle Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Battle Report</h1>
                @if($battle->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $battle->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    Battle
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            @if($battle->attacker)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <h4 class="font-medium text-red-900 dark:text-red-200">Attacker</h4>
                    <p class="text-lg font-semibold text-red-700 dark:text-red-300">{{ $battle->attacker->name }}</p>
                </div>
            @endif
            @if($battle->defender)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 dark:text-blue-200">Defender</h4>
                    <p class="text-lg font-semibold text-blue-700 dark:text-blue-300">{{ $battle->defender->name }}</p>
                </div>
            @endif
        </div>

        @if($battle->village)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Target Village</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $battle->village->name }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($battle->attacker_losses)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Attacker Losses</h4>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($battle->attacker_losses) }}</p>
                </div>
            @endif
            @if($battle->defender_losses)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Defender Losses</h4>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($battle->defender_losses) }}</p>
                </div>
            @endif
        </div>

        @if($battle->loot)
            <div class="mt-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Loot</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if(isset($battle->loot['wood']))
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                            <h5 class="font-medium text-green-900 dark:text-green-200">Wood</h5>
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ number_format($battle->loot['wood']) }}</p>
                        </div>
                    @endif
                    @if(isset($battle->loot['clay']))
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                            <h5 class="font-medium text-orange-900 dark:text-orange-200">Clay</h5>
                            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ number_format($battle->loot['clay']) }}</p>
                        </div>
                    @endif
                    @if(isset($battle->loot['iron']))
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <h5 class="font-medium text-gray-900 dark:text-white">Iron</h5>
                            <p class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ number_format($battle->loot['iron']) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Comments Section -->
    <div class="mt-6">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Battle Discussion
        </h4>
        <livewire:comment-section :model="$battle" />
    </div>
</div>