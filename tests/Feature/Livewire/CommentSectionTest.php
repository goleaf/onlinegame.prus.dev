<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CommentSection;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentSectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_render_comment_section()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_comments_and_form()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment content',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->assertSee('Test comment content')
            ->assertSee('Add Comment');
    }

    /**
     * @test
     */
    public function it_can_add_new_comment()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('newComment', 'This is a new comment')
            ->call('addComment')
            ->assertSee('This is a new comment')
            ->assertSet('newComment', '');
    }

    /**
     * @test
     */
    public function it_validates_comment_content()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('newComment', '')
            ->call('addComment')
            ->assertHasErrors(['newComment']);
    }

    /**
     * @test
     */
    public function it_can_reply_to_comment()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('replyingTo', $parentComment->id)
            ->set('replyContent', 'This is a reply')
            ->call('addReply')
            ->assertSee('This is a reply');
    }

    /**
     * @test
     */
    public function it_can_cancel_reply()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('replyingTo', 1)
            ->set('replyContent', 'This is a reply')
            ->call('cancelReply')
            ->assertSet('replyingTo', null)
            ->assertSet('replyContent', '');
    }

    /**
     * @test
     */
    public function it_can_edit_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Original content',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('editComment', $comment->id)
            ->assertSet('editingComment', $comment->id)
            ->assertSee('Update Comment');
    }

    /**
     * @test
     */
    public function it_can_update_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Original content',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('editingComment', $comment->id)
            ->set('editContent', 'Updated content')
            ->call('updateComment')
            ->assertSee('Updated content')
            ->assertSet('editingComment', null);
    }

    /**
     * @test
     */
    public function it_can_delete_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Comment to delete',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('deleteComment', $comment->id)
            ->assertDontSee('Comment to delete');
    }

    /**
     * @test
     */
    public function it_cannot_delete_other_users_comment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user2->id,
            'content' => 'Other user comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user1->id,
        ]);

        Livewire::actingAs($user1)
            ->test(CommentSection::class, [
                'commentableId' => $user1->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('deleteComment', $comment->id)
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_can_like_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('likeComment', $comment->id)
            ->assertEmitted('commentLiked');
    }

    /**
     * @test
     */
    public function it_can_unlike_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('unlikeComment', $comment->id)
            ->assertEmitted('commentUnliked');
    }

    /**
     * @test
     */
    public function it_can_sort_comments_by_newest()
    {
        $user = User::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Old comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'New comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('sortBy', 'newest')
            ->assertSeeInOrder(['New comment', 'Old comment']);
    }

    /**
     * @test
     */
    public function it_can_sort_comments_by_oldest()
    {
        $user = User::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Old comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'New comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('sortBy', 'oldest')
            ->assertSeeInOrder(['Old comment', 'New comment']);
    }

    /**
     * @test
     */
    public function it_can_filter_comments_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Comment::factory()->create([
            'user_id' => $user1->id,
            'content' => 'Comment from user 1',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user1->id,
        ]);

        Comment::factory()->create([
            'user_id' => $user2->id,
            'content' => 'Comment from user 2',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user1->id,
        ]);

        Livewire::actingAs($user1)
            ->test(CommentSection::class, [
                'commentableId' => $user1->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('filterByUser', $user1->id)
            ->assertSee('Comment from user 1')
            ->assertDontSee('Comment from user 2');
    }

    /**
     * @test
     */
    public function it_can_search_comments()
    {
        $user = User::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'This is about Laravel',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'This is about PHP',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->set('search', 'Laravel')
            ->assertSee('This is about Laravel')
            ->assertDontSee('This is about PHP');
    }

    /**
     * @test
     */
    public function it_can_load_more_comments()
    {
        $user = User::factory()->create();

        // Create more comments than the initial limit
        Comment::factory()->count(15)->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('loadMore')
            ->assertSee('Load More');
    }

    /**
     * @test
     */
    public function it_shows_comment_count()
    {
        $user = User::factory()->create();

        Comment::factory()->count(5)->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->assertSee('5 comments');
    }

    /**
     * @test
     */
    public function it_can_refresh_comments()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->call('refreshComments')
            ->assertEmitted('commentsRefreshed');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(CommentSection::class, [
            'commentableId' => 1,
            'commentableType' => 'App\Models\User',
        ])
            ->assertSee('Please login to view comments');
    }

    /**
     * @test
     */
    public function it_can_toggle_comment_form()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentSection::class, [
                'commentableId' => $user->id,
                'commentableType' => 'App\Models\User',
            ])
            ->assertSee('Add Comment')
            ->call('toggleCommentForm')
            ->assertDontSee('Add Comment')
            ->call('toggleCommentForm')
            ->assertSee('Add Comment');
    }
}
