<div class="max-w-4xl mx-auto p-6">
    <!-- Alliance Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $alliance->name }}</h1>
                @if($alliance->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $alliance->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    Alliance
                </span>
            </div>
        </div>

        @if($alliance->description)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $alliance->description }}</p>
            </div>
        @endif

        @if($alliance->tag)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tag</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $alliance->tag }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Members</h4>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $alliance->members_count ?? 0 }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Points</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($alliance->total_points ?? 0) }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Rank</h4>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">#{{ $alliance->rank ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="mt-6">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Alliance Discussion
        </h4>
        <livewire:comment-section :model="$alliance" />
    </div>
</div>