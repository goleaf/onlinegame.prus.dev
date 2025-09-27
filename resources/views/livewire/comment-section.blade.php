<div class="space-y-6">
    <!-- Comment Form -->
    @auth
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Add a Comment
            </h3>
            
            <form wire:submit="addComment" class="space-y-4">
                <div>
                    <textarea 
                        wire:model="newComment"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Share your thoughts..."
                        required
                    ></textarea>
                    @error('newComment') 
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                    @enderror
                </div>
                
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                    >
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 text-center">
            <p class="text-gray-600 dark:text-gray-400">
                Please <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">login</a> to comment.
            </p>
        </div>
    @endauth

    <!-- Comments List -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Comments ({{ $comments->count() }})
        </h3>
        
        @forelse($comments as $comment)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 {{ $comment->is_pinned ? 'ring-2 ring-yellow-400' : '' }}">
                <!-- Comment Header -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                            {{ substr($comment->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $comment->user->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $comment->created_at->diffForHumans() }}
                                @if($comment->is_pinned)
                                    <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        📌 Pinned
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    @auth
                        <div class="flex items-center space-x-2">
                            @if(auth()->user()->can('pin-comments'))
                                <button 
                                    wire:click="togglePin({{ $comment->id }})"
                                    class="text-gray-400 hover:text-yellow-500 transition-colors"
                                    title="Toggle Pin"
                                >
                                    📌
                                </button>
                            @endif
                            
                            @if($comment->user_id === auth()->id() || auth()->user()->can('delete-comments'))
                                <button 
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="Are you sure you want to delete this comment? This action cannot be undone."
                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                    title="Delete Comment"
                                >
                                    🗑️
                                </button>
                            @endif
                        </div>
                    @endauth
                </div>
                
                <!-- Comment Content -->
                <div class="mb-4">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->content }}</p>
                </div>
                
                <!-- Reply Button -->
                @auth
                    @if($comment->canBeRepliedTo())
                        <button 
                            wire:click="replyTo({{ $comment->id }})"
                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors"
                        >
                            Reply
                        </button>
                    @endif
                @endauth
                
                <!-- Replies -->
                @if($comment->approvedReplies->count() > 0)
                    <div class="mt-4 ml-8 space-y-3">
                        @foreach($comment->approvedReplies as $reply)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-2">
                                    <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-medium">
                                        {{ substr($reply->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $reply->user->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</p>
                                    </div>
                                    
                                    @auth
                                        @if($reply->user_id === auth()->id() || auth()->user()->can('delete-comments'))
                                            <button 
                                                wire:click="deleteComment({{ $reply->id }})"
                                                wire:confirm="Are you sure you want to delete this reply? This action cannot be undone."
                                                class="text-gray-400 hover:text-red-500 transition-colors ml-auto"
                                                title="Delete Reply"
                                            >
                                                🗑️
                                            </button>
                                        @endif
                                    @endauth
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No comments yet. Be the first to comment!</p>
            </div>
        @endforelse
    </div>
    
    <!-- Reply Form -->
    @if($showReplyForm)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Reply to Comment</h4>
            
            <form wire:submit="addReply" class="space-y-3">
                <div>
                    <textarea 
                        wire:model="replyContent"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Write your reply..."
                        required
                    ></textarea>
                    @error('replyContent') 
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> 
                    @enderror
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button 
                        type="button"
                        wire:click="cancelReply"
                        class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                    >
                        Post Reply
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
