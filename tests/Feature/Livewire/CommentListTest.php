<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CommentList;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_render_comment_list()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentList::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_comments()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment content',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->assertSee('Test comment content')
            ->assertSee($user->name);
    }

    /**
     * @test
     */
    public function it_can_paginate_comments()
    {
        $user = User::factory()->create();

        // Create more comments than the pagination limit
        Comment::factory()->count(25)->create([
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->assertSee('Next')
            ->assertSee('Previous');
    }

    /**
     * @test
     */
    public function it_can_sort_comments_by_newest()
    {
        $user = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Old comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);

        $newComment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'New comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->set('sortBy', 'newest')
            ->assertSeeInOrder(['New comment', 'Old comment']);
    }

    /**
     * @test
     */
    public function it_can_sort_comments_by_oldest()
    {
        $user = User::factory()->create();

        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Old comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now()->subDays(1),
        ]);

        $newComment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'New comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
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
            ->test(CommentList::class, ['commentableId' => $user1->id, 'commentableType' => 'App\Models\User'])
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
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->set('search', 'Laravel')
            ->assertSee('This is about Laravel')
            ->assertDontSee('This is about PHP');
    }

    /**
     * @test
     */
    public function it_can_show_comment_replies()
    {
        $user = User::factory()->create();

        $parentComment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Parent comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        $reply = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Reply to parent',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->assertSee('Parent comment')
            ->assertSee('Reply to parent');
    }

    /**
     * @test
     */
    public function it_can_toggle_reply_visibility()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->assertDontSee('Reply form')
            ->call('toggleReply', $comment->id)
            ->assertSee('Reply form');
    }

    /**
     * @test
     */
    public function it_can_like_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
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
            'content' => 'Test comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->call('unlikeComment', $comment->id)
            ->assertEmitted('commentUnliked');
    }

    /**
     * @test
     */
    public function it_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->call('deleteComment', $comment->id)
            ->assertEmitted('commentDeleted')
            ->assertDontSee('Test comment');
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
            ->test(CommentList::class, ['commentableId' => $user1->id, 'commentableType' => 'App\Models\User'])
            ->call('deleteComment', $comment->id)
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_can_edit_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Original content',
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->call('editComment', $comment->id)
            ->assertEmitted('commentEditRequested', $comment->id);
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
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->assertSee('5 comments');
    }

    /**
     * @test
     */
    public function it_can_refresh_comments()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentList::class, ['commentableId' => $user->id, 'commentableType' => 'App\Models\User'])
            ->call('refreshComments')
            ->assertEmitted('commentsRefreshed');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(CommentList::class, ['commentableId' => 1, 'commentableType' => 'App\Models\User'])
            ->assertSee('Please login to view comments');
    }
}
