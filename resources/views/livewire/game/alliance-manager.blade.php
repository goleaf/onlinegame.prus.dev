<div class="alliance-manager">
    {{-- Alliance Management Interface --}}
    
    {{-- Navigation Tabs --}}
    <div class="alliance-tabs mb-6">
        <nav class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
            <button wire:click="$set('showDiplomacy', false)" 
                    class="px-4 py-2 rounded-md text-sm font-medium {{ !$showDiplomacy && !$showWars && !$showMessages && !$showLogs ? 'bg-white text-blue-700 shadow' : 'text-gray-500 hover:text-gray-700' }}">
                Overview
            </button>
            <button wire:click="toggleDiplomacy" 
                    class="px-4 py-2 rounded-md text-sm font-medium {{ $showDiplomacy ? 'bg-white text-blue-700 shadow' : 'text-gray-500 hover:text-gray-700' }}">
                Diplomacy
            </button>
            <button wire:click="toggleWars" 
                    class="px-4 py-2 rounded-md text-sm font-medium {{ $showWars ? 'bg-white text-blue-700 shadow' : 'text-gray-500 hover:text-gray-700' }}">
                Wars
            </button>
            <button wire:click="toggleMessages" 
                    class="px-4 py-2 rounded-md text-sm font-medium {{ $showMessages ? 'bg-white text-blue-700 shadow' : 'text-gray-500 hover:text-gray-700' }}">
                Messages
            </button>
            <button wire:click="toggleLogs" 
                    class="px-4 py-2 rounded-md text-sm font-medium {{ $showLogs ? 'bg-white text-blue-700 shadow' : 'text-gray-500 hover:text-gray-700' }}">
                Activity Log
            </button>
        </nav>
    </div>

    {{-- Overview Tab --}}
    @if (!$showDiplomacy && !$showWars && !$showMessages && !$showLogs)
        <div class="alliance-overview">
            @if ($myAlliance)
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ $myAlliance['name'] }} [{{ $myAlliance['tag'] }}]</h2>
                    <p class="text-gray-600 mb-4">{{ $myAlliance['description'] ?? 'No description available.' }}</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-blue-900">Members</h3>
                            <p class="text-2xl font-bold text-blue-700">{{ count($allianceMembers) }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-900">Total Points</h3>
                            <p class="text-2xl font-bold text-green-700">{{ number_format($myAlliance['points'] ?? 0) }}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-purple-900">Villages</h3>
                            <p class="text-2xl font-bold text-purple-700">{{ $myAlliance['villages_count'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <button wire:click="toggleDiplomacy" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Manage Diplomacy
                        </button>
                        <button wire:click="toggleMessages" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Alliance Messages
                        </button>
                        <button wire:click="toggleWars" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            War Status
                        </button>
                        <button wire:click="toggleLogs" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            View Activity
                        </button>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Alliance</h3>
                    <p class="text-yellow-700">You are not currently a member of any alliance. Join or create an alliance to access these features.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Diplomacy Tab --}}
    @if ($showDiplomacy)
        <div class="diplomacy-section">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Alliance Diplomacy</h3>
                
                {{-- Propose New Diplomacy --}}
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-medium text-gray-900 mb-3">Propose Diplomacy</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target Alliance</label>
                            <select wire:model="diplomacyForm.target_alliance_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Alliance</option>
                                @foreach ($alliances as $alliance)
                                    @if ($alliance['id'] !== $myAlliance['id'])
                                        <option value="{{ $alliance['id'] }}">{{ $alliance['name'] }} [{{ $alliance['tag'] }}]</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Diplomacy Type</label>
                            <select wire:model="diplomacyForm.status" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="ally">Alliance</option>
                                <option value="non_aggression_pact">Non-Aggression Pact</option>
                                <option value="trade_agreement">Trade Agreement</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea wire:model="diplomacyForm.message" rows="3" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Optional message to accompany your proposal..."></textarea>
                    </div>
                    <div class="mt-4">
                        <button wire:click="proposeDiplomacy" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Send Proposal
                        </button>
                    </div>
                </div>

                {{-- Current Diplomacy --}}
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Current Relations</h4>
                    @if (count($allianceDiplomacy) > 0)
                        <div class="space-y-3">
                            @foreach ($allianceDiplomacy as $diplomacy)
                                <div class="border rounded-lg p-4 {{ $diplomacy['response_status'] === 'pending' ? 'border-yellow-300 bg-yellow-50' : ($diplomacy['response_status'] === 'accepted' ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50') }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h5 class="font-medium">
                                                {{ $diplomacy['alliance_id'] === $myAlliance['id'] ? $diplomacy['target_alliance']['name'] : $diplomacy['alliance']['name'] }}
                                                [{{ $diplomacy['alliance_id'] === $myAlliance['id'] ? $diplomacy['target_alliance']['tag'] : $diplomacy['alliance']['tag'] }}]
                                            </h5>
                                            <p class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $diplomacy['status'])) }}</p>
                                            <p class="text-xs text-gray-500">
                                                Status: {{ ucfirst($diplomacy['response_status']) }} • 
                                                Proposed: {{ \Carbon\Carbon::parse($diplomacy['proposed_at'])->diffForHumans() }}
                                            </p>
                                            @if ($diplomacy['message'])
                                                <p class="text-sm text-gray-700 mt-2 italic">"{{ $diplomacy['message'] }}"</p>
                                            @endif
                                        </div>
                                        @if ($diplomacy['response_status'] === 'pending')
                                            <div class="flex space-x-2">
                                                @if ($diplomacy['target_alliance_id'] === $myAlliance['id'])
                                                    <button wire:click="respondToDiplomacy({{ $diplomacy['id'] }}, 'accepted')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                                        Accept
                                                    </button>
                                                    <button wire:click="respondToDiplomacy({{ $diplomacy['id'] }}, 'declined')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                                                        Decline
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No diplomatic relations established.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Wars Tab --}}
    @if ($showWars)
        <div class="wars-section">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Alliance Wars</h3>
                
                {{-- Declare War --}}
                <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
                    <h4 class="font-medium text-red-900 mb-3">Declare War</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-red-700 mb-1">Target Alliance</label>
                            <select wire:model="warForm.target_alliance_id" class="w-full border-red-300 rounded-md shadow-sm">
                                <option value="">Select Alliance</option>
                                @foreach ($alliances as $alliance)
                                    @if ($alliance['id'] !== $myAlliance['id'])
                                        <option value="{{ $alliance['id'] }}">{{ $alliance['name'] }} [{{ $alliance['tag'] }}]</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-red-700 mb-1">Declaration Message</label>
                        <textarea wire:model="warForm.declaration_message" rows="3" class="w-full border-red-300 rounded-md shadow-sm" placeholder="War declaration message..."></textarea>
                    </div>
                    <div class="mt-4">
                        <button wire:click="declareWar" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Declare War
                        </button>
                    </div>
                </div>

                {{-- Current Wars --}}
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Active Wars</h4>
                    @if (count($allianceWars) > 0)
                        <div class="space-y-3">
                            @foreach ($allianceWars as $war)
                                <div class="border border-red-300 bg-red-50 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h5 class="font-medium text-red-900">
                                                {{ $war['attacker_alliance_id'] === $myAlliance['id'] ? 'War against' : 'War from' }}
                                                {{ $war['attacker_alliance_id'] === $myAlliance['id'] ? $war['defender_alliance']['name'] : $war['attacker_alliance']['name'] }}
                                                [{{ $war['attacker_alliance_id'] === $myAlliance['id'] ? $war['defender_alliance']['tag'] : $war['attacker_alliance']['tag'] }}]
                                            </h5>
                                            <p class="text-sm text-red-700">Status: {{ ucfirst($war['status']) }}</p>
                                            <p class="text-xs text-red-600">
                                                Declared: {{ \Carbon\Carbon::parse($war['declared_at'])->diffForHumans() }}
                                                @if ($war['started_at'])
                                                    • Started: {{ \Carbon\Carbon::parse($war['started_at'])->diffForHumans() }}
                                                @endif
                                            </p>
                                            @if ($war['declaration_message'])
                                                <p class="text-sm text-red-800 mt-2 italic">"{{ $war['declaration_message'] }}"</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $war['status'] === 'active' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($war['status']) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No active wars.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Messages Tab --}}
    @if ($showMessages)
        <div class="messages-section">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Alliance Messages</h3>
                
                {{-- Post New Message --}}
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="font-medium text-blue-900 mb-3">Post Message</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">Message Type</label>
                            <select wire:model="messageForm.message_type" class="w-full border-blue-300 rounded-md shadow-sm">
                                <option value="general">General</option>
                                <option value="announcement">Announcement</option>
                                <option value="war">War Related</option>
                                <option value="diplomacy">Diplomacy</option>
                                <option value="trade">Trade</option>
                                <option value="strategy">Strategy</option>
                                <option value="social">Social</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">Priority</label>
                            <select wire:model="messageForm.priority" class="w-full border-blue-300 rounded-md shadow-sm">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Subject</label>
                        <input type="text" wire:model="messageForm.subject" class="w-full border-blue-300 rounded-md shadow-sm" placeholder="Message subject">
                    </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Content</label>
                        <textarea wire:model="messageForm.content" rows="4" class="w-full border-blue-300 rounded-md shadow-sm" placeholder="Message content..."></textarea>
                    </div>
                    <div class="mt-4 flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="messageForm.is_pinned" class="rounded border-blue-300 text-blue-600">
                            <span class="ml-2 text-sm text-blue-700">Pin Message</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="messageForm.is_important" class="rounded border-blue-300 text-blue-600">
                            <span class="ml-2 text-sm text-blue-700">Mark as Important</span>
                        </label>
                    </div>
                    <div class="mt-4">
                        <button wire:click="postMessage" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Post Message
                        </button>
                    </div>
                </div>

                {{-- Messages List --}}
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Recent Messages</h4>
                    @if (count($allianceMessages) > 0)
                        <div class="space-y-3">
                            @foreach ($allianceMessages as $message)
                                <div class="border rounded-lg p-4 {{ $message['is_pinned'] ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 bg-white' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h5 class="font-medium">{{ $message['title'] }}</h5>
                                                @if ($message['is_pinned'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pinned</span>
                                                @endif
                                                @if ($message['is_important'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Important</span>
                                                @endif
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($message['type']) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 mb-2">{{ $message['content'] }}</p>
                                            <p class="text-xs text-gray-500">
                                                By {{ $message['sender']['name'] ?? 'Unknown' }} • 
                                                {{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                        <button wire:click="markMessageAsRead({{ $message['id'] }})" class="text-blue-600 hover:text-blue-800 text-xs">
                                            Mark Read
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No messages posted yet.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Activity Log Tab --}}
    @if ($showLogs)
        <div class="logs-section">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Alliance Activity Log</h3>
                
                @if (count($allianceLogs) > 0)
                    <div class="space-y-3">
                        @foreach ($allianceLogs as $log)
                            <div class="border-l-4 {{ $this->getLogBorderColor($log['action']) }} pl-4 py-2">
                                <p class="text-sm text-gray-900">{{ $log['description'] }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No activity recorded yet.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Notifications --}}
    @if (count($notifications) > 0)
        <div class="fixed bottom-4 right-4 space-y-2">
            @foreach ($notifications as $notification)
                <div class="bg-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-100 border border-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-400 text-{{ $notification['type'] === 'error' ? 'red' : ($notification['type'] === 'success' ? 'green' : 'blue') }}-700 px-4 py-3 rounded">
                    {{ $notification['message'] }}
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    // Helper function to get log border color
    window.getLogBorderColor = function(action) {
        const colors = {
            'member_joined': 'border-green-400',
            'member_left': 'border-yellow-400',
            'member_kicked': 'border-red-400',
            'member_promoted': 'border-blue-400',
            'member_demoted': 'border-orange-400',
            'diplomacy_proposed': 'border-purple-400',
            'diplomacy_accepted': 'border-green-400',
            'diplomacy_declined': 'border-red-400',
            'war_declared': 'border-red-600',
            'war_ended': 'border-gray-400',
            'message_posted': 'border-blue-400',
            'default': 'border-gray-300'
        };
        return colors[action] || colors['default'];
    };
</script>
