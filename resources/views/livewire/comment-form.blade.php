<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        @if($parentId)
            Reply to Comment
        @else
            Add a Comment
        @endif
    </h3>
    
    <form wire:submit="submit" class="space-y-4">
        <div>
            <textarea 
                wire:model="commentContent"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Share your thoughts..."
                required
            ></textarea>
            @error('commentContent') 
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
            @enderror
        </div>
        
        <div class="flex justify-end space-x-2">
            @if($parentId)
                <button 
                    type="button"
                    wire:click.prevent="$dispatch('cancel-reply')"
                    class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                >
                    Cancel
                </button>
            @endif
            <button 
                type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
            >
                @if($parentId)
                    Post Reply
                @else
                    Post Comment
                @endif
            </button>
        </div>
    </form>
</div>
