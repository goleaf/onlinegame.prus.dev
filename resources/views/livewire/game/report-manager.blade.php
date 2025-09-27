<div class="report-manager-container">
    <!-- Header with controls -->
    <div class="report-header bg-gradient-to-r from-red-600 to-red-800 text-white p-4 rounded-lg mb-6">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h2 class="text-2xl font-bold flex items-center">
                    📊 Battle Reports
                </h2>
                <div class="flex items-center space-x-2">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.live="realTimeUpdates" class="mr-2">
                        <span class="text-sm">Real-time</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model.live="autoRefresh" class="mr-2">
                        <span class="text-sm">Auto-refresh</span>
                    </label>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <select wire:model.live="refreshInterval" class="bg-red-700 text-white px-3 py-1 rounded">
                    <option value="5">5s</option>
                    <option value="10">10s</option>
                    <option value="15">15s</option>
                    <option value="30">30s</option>
                    <option value="60">1m</option>
                </select>
                <select wire:model.live="gameSpeed" class="bg-red-700 text-white px-3 py-1 rounded">
                    <option value="0.5">0.5x</option>
                    <option value="1">1x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2">2x</option>
                    <option value="3">3x</option>
                </select>
                <button wire:click="loadReportData" class="bg-red-700 hover:bg-red-800 px-4 py-2 rounded">
                    🔄 Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if (count($notifications) > 0)
        <div class="notifications mb-4">
            @foreach ($notifications as $notification)
                <div class="alert alert-{{ $notification['type'] }} mb-2 p-3 rounded flex justify-between items-center">
                    <span>{{ $notification['message'] }}</span>
                    <button wire:click="removeNotification('{{ $notification['id'] }}')" class="text-sm">×</button>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="text-2xl mr-3">📊</div>
                <div>
                    <div class="text-sm text-gray-600">Total Reports</div>
                    <div class="text-2xl font-bold">{{ $reportStats['total_reports'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="bg-red-100 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="text-2xl mr-3">🔴</div>
                <div>
                    <div class="text-sm text-gray-600">Unread</div>
                    <div class="text-2xl font-bold">{{ $reportStats['unread_reports'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="bg-yellow-100 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="text-2xl mr-3">⭐</div>
                <div>
                    <div class="text-sm text-gray-600">Important</div>
                    <div class="text-2xl font-bold">{{ $reportStats['important_reports'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="bg-green-100 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="text-2xl mr-3">📅</div>
                <div>
                    <div class="text-sm text-gray-600">Today</div>
                    <div class="text-2xl font-bold">{{ $reportStats['today_reports'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Controls -->
    <div class="bg-gray-100 p-4 rounded-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Type Filter -->
            <div>
                <label class="block text-sm font-medium mb-2">Report Type</label>
                <select wire:model.live="filterByType" class="w-full p-2 border rounded">
                    <option value="">All Types</option>
                    @foreach ($reportTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <select wire:model.live="filterByStatus" class="w-full p-2 border rounded">
                    <option value="">All Status</option>
                    @foreach ($statusTypes as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-sm font-medium mb-2">Date Range</label>
                <select wire:model.live="filterByDate" class="w-full p-2 border rounded">
                    <option value="">All Time</option>
                    @foreach ($dateRanges as $range)
                        <option value="{{ $range }}">{{ ucfirst($range) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 mt-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input type="text" wire:model.live.debounce.300ms="searchQuery"
                       placeholder="Search reports..."
                       class="w-full p-2 border rounded">
            </div>

            <!-- Toggle Filters -->
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showOnlyUnread" class="mr-2">
                    <span class="text-sm">Unread only</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showOnlyImportant" class="mr-2">
                    <span class="text-sm">Important only</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showOnlyMyReports" class="mr-2">
                    <span class="text-sm">My reports only</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
                <button wire:click="markAllAsRead"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                    Mark All Read
                </button>
                <button wire:click="deleteAllRead"
                        wire:confirm="Are you sure you want to delete all read reports? This action cannot be undone."
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                    Delete Read
                </button>
                <button wire:click="clearFilters"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Reports List -->
    <div class="bg-white rounded-lg shadow">
        @if ($isLoading)
            <div class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading reports...</p>
            </div>
        @elseif(count($reports) === 0)
            <div class="p-8 text-center text-gray-500">
                <div class="text-4xl mb-4">📄</div>
                <p>No reports found</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <button wire:click="sortReports('type')" class="flex items-center">
                                    Type
                                    @if ($sortBy === 'type')
                                        <span class="ml-1">{{ $sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Attacker</th>
                            <th class="px-4 py-3 text-left">Defender</th>
                            <th class="px-4 py-3 text-left">
                                <button wire:click="sortReports('status')" class="flex items-center">
                                    Status
                                    @if ($sortBy === 'status')
                                        <span class="ml-1">{{ $sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button wire:click="sortReports('created_at')" class="flex items-center">
                                    Date
                                    @if ($sortBy === 'created_at')
                                        <span class="ml-1">{{ $sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr
                                class="border-b hover:bg-gray-50 {{ !$report['is_read'] ? 'bg-blue-50' : '' }} {{ $report['is_important'] ? 'bg-yellow-50' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-2">{{ $this->getReportIcon($report) }}</span>
                                        <span class="font-medium">{{ ucfirst($report['type']) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ $report['title'] }}</span>
                                        @if ($report['is_important'])
                                            <span class="ml-2 text-yellow-500">⭐</span>
                                        @endif
                                        @if (!$report['is_read'])
                                            <span class="ml-2 w-2 h-2 bg-blue-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <div class="font-medium">{{ $report['attacker']['name'] ?? 'Unknown' }}</div>
                                        <div class="text-gray-500">
                                            {{ $report['from_village']['name'] ?? 'Unknown Village' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <div class="font-medium">{{ $report['defender']['name'] ?? 'Unknown' }}</div>
                                        <div class="text-gray-500">
                                            {{ $report['to_village']['name'] ?? 'Unknown Village' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                          class="px-2 py-1 rounded text-sm font-medium
                                        {{ $this->getReportColor($report) === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $this->getReportColor($report) === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $this->getReportColor($report) === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $this->getReportColor($report) === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $this->getReportColor($report) === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $this->getReportStatus($report) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $this->getTimeAgo($report['created_at']) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('game.reports.detail', $report['id']) }}"
                                           class="text-blue-500 hover:text-blue-700 text-sm">
                                            View Details
                                        </a>
                                        <button wire:click="selectReport({{ $report['id'] }})"
                                                class="text-gray-500 hover:text-gray-700 text-sm">
                                            Quick View
                                        </button>
                                        @if ($report['is_read'])
                                            <button wire:click="markAsUnread({{ $report['id'] }})"
                                                    class="text-gray-500 hover:text-gray-700 text-sm">
                                                Mark Unread
                                            </button>
                                        @else
                                            <button wire:click="markAsRead({{ $report['id'] }})"
                                                    class="text-green-500 hover:text-green-700 text-sm">
                                                Mark Read
                                            </button>
                                        @endif
                                        @if ($report['is_important'])
                                            <button wire:click="markAsUnimportant({{ $report['id'] }})"
                                                    class="text-yellow-500 hover:text-yellow-700 text-sm">
                                                Unimportant
                                            </button>
                                        @else
                                            <button wire:click="markAsImportant({{ $report['id'] }})"
                                                    class="text-yellow-500 hover:text-yellow-700 text-sm">
                                                Important
                                            </button>
                                        @endif
                                        <button wire:click="deleteReport({{ $report['id'] }})"
                                                wire:confirm="Are you sure you want to delete this report? This action cannot be undone."
                                                class="text-red-500 hover:text-red-700 text-sm">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Report Details Modal -->
    @if ($showDetails && $selectedReport)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">{{ $selectedReport['title'] }}</h3>
                    <button wire:click="toggleDetails" class="text-gray-500 hover:text-gray-700">
                        <span class="text-2xl">×</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Report Info -->
                    <div>
                        <h4 class="font-semibold mb-2">Report Information</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Type:</strong> {{ ucfirst($selectedReport['type']) }}</div>
                            <div><strong>Status:</strong> {{ $this->getReportStatus($selectedReport) }}</div>
                            <div><strong>Date:</strong> {{ $selectedReport['created_at'] }}</div>
                            <div><strong>Attacker:</strong> {{ $selectedReport['attacker']['name'] ?? 'Unknown' }}
                            </div>
                            <div><strong>Defender:</strong> {{ $selectedReport['defender']['name'] ?? 'Unknown' }}
                            </div>
                        </div>
                    </div>

                    <!-- Battle Stats -->
                    <div>
                        <h4 class="font-semibold mb-2">Battle Statistics</h4>
                        <div class="space-y-2 text-sm">
                            <div><strong>Attacker Losses:</strong> {{ $selectedReport['attacker_losses'] ?? 0 }}</div>
                            <div><strong>Defender Losses:</strong> {{ $selectedReport['defender_losses'] ?? 0 }}</div>
                            <div><strong>Resources Looted:</strong> {{ $selectedReport['resources_looted'] ?? 0 }}
                            </div>
                            <div><strong>Experience Gained:</strong> {{ $selectedReport['experience_gained'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="mt-6">
                    <h4 class="font-semibold mb-2">Report Content</h4>
                    <div class="bg-gray-100 p-4 rounded">
                        {!! nl2br(e($reportContent)) !!}
                    </div>
                </div>

                <!-- Attachments -->
                @if (count($reportAttachments) > 0)
                    <div class="mt-6">
                        <h4 class="font-semibold mb-2">Attachments</h4>
                        <div class="space-y-2">
                            @foreach ($reportAttachments as $attachment)
                                <div class="flex items-center justify-between bg-gray-100 p-2 rounded">
                                    <span>{{ $attachment['name'] }}</span>
                                    <button class="text-blue-500 hover:text-blue-700 text-sm">Download</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-2">
                    @if ($selectedReport['is_read'])
                        <button wire:click="markAsUnread({{ $selectedReport['id'] }})"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                            Mark Unread
                        </button>
                    @else
                        <button wire:click="markAsRead({{ $selectedReport['id'] }})"
                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                            Mark Read
                        </button>
                    @endif

                    @if ($selectedReport['is_important'])
                        <button wire:click="markAsUnimportant({{ $selectedReport['id'] }})"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                            Unimportant
                        </button>
                    @else
                        <button wire:click="markAsImportant({{ $selectedReport['id'] }})"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                            Important
                        </button>
                    @endif

                    <button wire:click="deleteReport({{ $selectedReport['id'] }})"
                            wire:confirm="Are you sure you want to delete this report? This action cannot be undone."
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Real-time polling -->
    @if ($realTimeUpdates)
        <div wire:poll.{{ $refreshInterval }}s="loadReportData"></div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        // Real-time updates
        Livewire.on('initializeReportRealTime', (data) => {
            console.log('Report real-time initialized:', data);
        });

        // Report notifications
        Livewire.on('reportReceived', (data) => {
            console.log('New report received:', data);
            // Show browser notification if permission granted
            if (Notification.permission === 'granted') {
                new Notification('New Battle Report', {
                    body: 'You have received a new battle report',
                    icon: '/favicon.ico'
                });
            }
        });

        // Auto-refresh handling
        Livewire.on('reportUpdated', (data) => {
            console.log('Report updated:', data);
        });

        // Error handling
        Livewire.on('reportError', (data) => {
            console.error('Report error:', data);
        });
    });

    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
</script>
