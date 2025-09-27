<div class="max-w-4xl mx-auto p-6">
    <!-- Quest Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $quest->name }}</h1>
                @if($quest->reference_number)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $quest->reference_number }}
                    </span>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $quest->difficulty === 'easy' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                       ($quest->difficulty === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                    {{ ucfirst($quest->difficulty) }}
                </span>
                @if($quest->is_repeatable)
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        Repeatable
                    </span>
                @endif
            </div>
        </div>

        @if($quest->description)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $quest->description }}</p>
            </div>
        @endif

        @if($quest->instructions)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Instructions</h3>
                <p class="text-gray-700 dark:text-gray-300">{{ $quest->instructions }}</p>
            </div>
        @endif

        @if($quest->requirements)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Requirements</h3>
                <ul class="list-disc list-inside text-gray-700 dark:text-gray-300">
                    @foreach($quest->requirements as $requirement)
                        <li>{{ $requirement }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($quest->rewards)
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Rewards</h3>
                <ul class="list-disc list-inside text-gray-700 dark:text-gray-300">
                    @foreach($quest->rewards as $reward)
                        <li>{{ $reward }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($quest->experience_reward || $quest->gold_reward)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($quest->experience_reward)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Experience Reward</h4>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($quest->experience_reward) }} XP</p>
                    </div>
                @endif
                @if($quest->gold_reward)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Gold Reward</h4>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($quest->gold_reward) }} Gold</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Comments Section -->
    <livewire:game.quest-comments :quest="$quest" />
</div>
