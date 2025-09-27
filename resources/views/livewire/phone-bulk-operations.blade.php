<div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-semibold">Phone Number Bulk Operations</h3>
        <div class="flex space-x-2">
            <button 
                wire:click="selectAllUsers"
                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
                Select All
            </button>
            <button 
                wire:click="clearSelection"
                class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"
            >
                Clear Selection
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Operation Selection -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h4 class="text-lg font-semibold mb-4">Select Operation</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <label class="flex items-center">
                <input 
                    type="radio" 
                    wire:model="operation" 
                    value="export" 
                    class="mr-2"
                >
                Export Phone Numbers (CSV)
            </label>
            <label class="flex items-center">
                <input 
                    type="radio" 
                    wire:model="operation" 
                    value="validate" 
                    class="mr-2"
                >
                Validate Phone Numbers
            </label>
            <label class="flex items-center">
                <input 
                    type="radio" 
                    wire:model="operation" 
                    value="format" 
                    class="mr-2"
                >
                Format Phone Numbers
            </label>
            <label class="flex items-center">
                <input 
                    type="radio" 
                    wire:model="operation" 
                    value="update_from_csv" 
                    class="mr-2"
                >
                Update from CSV
            </label>
            <label class="flex items-center">
                <input 
                    type="radio" 
                    wire:model="operation" 
                    value="delete" 
                    class="mr-2"
                >
                Delete Phone Numbers
            </label>
        </div>

        @if($operation === 'update_from_csv')
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload CSV File
                </label>
                <input 
                    type="file" 
                    wire:model="csvFile" 
                    accept=".csv,.txt"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-sm text-gray-500 mt-1">
                    CSV format: email,phone,country (optional)
                </p>
                @error('csvFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="mt-4">
            <button 
                wire:click="processBulkOperation"
                wire:loading.attr="disabled"
                class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="processBulkOperation">Execute Operation</span>
                <span wire:loading wire:target="processBulkOperation">Processing...</span>
            </button>
        </div>
    </div>

    <!-- Results -->
    @if(!empty($results))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-lg font-semibold mb-2">Operation Results</h4>
            <p class="text-blue-800">{{ $results['message'] }}</p>
            
            @if(isset($results['csv_data']) && $results['operation'] === 'export')
                <div class="mt-4">
                    <button 
                        onclick="downloadCSV('{{ base64_encode($results['csv_data']) }}', 'phone_numbers_export.csv')"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                    >
                        Download CSV
                    </button>
                </div>
            @endif

            @if(isset($results['errors']) && !empty($results['errors']))
                <div class="mt-4">
                    <h5 class="font-semibold text-red-800">Errors:</h5>
                    <ul class="list-disc list-inside text-red-700 text-sm">
                        @foreach($results['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- User Selection -->
    <div class="mb-6">
        <h4 class="text-lg font-semibold mb-4">
            Select Users ({{ count($selectedUsers) }} selected)
        </h4>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input 
                                type="checkbox" 
                                wire:model="selectAll"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E164</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <input 
                                    type="checkbox" 
                                    wire:model="selectedUsers" 
                                    value="{{ $user->id }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $user->phone }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $user->phone_country }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->phone_e164 }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
function downloadCSV(csvData, filename) {
    const blob = new Blob([atob(csvData)], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>