<div>
    <div class="mb-4">
        <button 
            wire:click="showCreateForm" 
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
            Create New Village
        </button>
    </div>

    @if($showForm)
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h3 class="text-lg font-semibold mb-4">Create New Village</h3>
            
            {!! $form->render() !!}
            
            <div class="flex justify-end space-x-2 mt-4">
                <button 
                    wire:click="hideForm" 
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Cancel
                </button>
                <button 
                    wire:click="store" 
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create Village
                </button>
            </div>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
</div>

