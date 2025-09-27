<div class="max-w-4xl mx-auto p-6">
    <!-- Village Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $village->name }}</h1>
                @if($village->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $village->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                @if($village->is_capital)
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Capital
                    </span>
                @endif
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Village
                </span>
            </div>
        </div>

        @if($village->coordinates)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Coordinates</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $village->coordinates->x }}, {{ $village->coordinates->y }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Population</h4>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($village->population) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Wood</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($village->wood) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Clay</h4>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($village->clay) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Iron</h4>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($village->iron) }}</p>
            </div>
        </div>

        @if($village->player)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Owner</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $village->player->name }}</p>
            </div>
        @endif
    </div>

    <!-- Comments Section -->
    <div class="mt-6">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Village Discussion
        </h4>
        <livewire:comment-section :model="$village" />
    </div>
</div>