<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CommentForm;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommentFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_render_comment_form()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_submit_comment_with_valid_data()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', 'This is a test comment')
            ->set('commentable_type', 'App\Models\User')
            ->set('commentable_id', $user->id)
            ->call('submit')
            ->assertEmitted('commentAdded')
            ->assertSet('content', '');

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'user_id' => $user->id,
            'commentable_type' => 'App\Models\User',
            'commentable_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', '')
            ->call('submit')
            ->assertHasErrors(['content' => 'required']);
    }

    /**
     * @test
     */
    public function it_validates_content_length()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', str_repeat('a', 1001))
            ->call('submit')
            ->assertHasErrors(['content' => 'max']);
    }

    /**
     * @test
     */
    public function it_can_reply_to_existing_comment()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class, ['parentId' => $parentComment->id])
            ->set('content', 'This is a reply')
            ->call('submit')
            ->assertEmitted('commentAdded');

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply',
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_edit_existing_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(CommentForm::class, ['comment' => $comment])
            ->set('content', 'Updated comment content')
            ->call('submit')
            ->assertEmitted('commentUpdated');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment content',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_unauthorized_comment_editing()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($user)
            ->test(CommentForm::class, ['comment' => $comment])
            ->set('content', 'Unauthorized edit attempt')
            ->call('submit')
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_can_cancel_comment_editing()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(CommentForm::class, ['comment' => $comment])
            ->set('content', 'Modified content')
            ->call('cancel')
            ->assertSet('content', $comment->content)
            ->assertEmitted('commentEditCancelled');
    }

    /**
     * @test
     */
    public function it_can_handle_comment_submission_error()
    {
        $user = User::factory()->create();

        // Mock database error
        Comment::shouldReceive('create')->andThrow(new \Exception('Database error'));

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', 'Test comment')
            ->set('commentable_type', 'App\Models\User')
            ->set('commentable_id', $user->id)
            ->call('submit')
            ->assertHasErrors(['general' => 'Database error']);
    }

    /**
     * @test
     */
    public function it_shows_character_count()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', 'Hello world')
            ->assertSee('11/1000');
    }

    /**
     * @test
     */
    public function it_handles_max_length_warning()
    {
        $user = User::factory()->create();
        $longContent = str_repeat('a', 900);

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', $longContent)
            ->assertSee('approaching limit');
    }

    /**
     * @test
     */
    public function it_can_clear_form()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', 'Some content')
            ->call('clear')
            ->assertSet('content', '');
    }

    /**
     * @test
     */
    public function it_can_toggle_preview_mode()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->assertSet('showPreview', false)
            ->call('togglePreview')
            ->assertSet('showPreview', true)
            ->call('togglePreview')
            ->assertSet('showPreview', false);
    }

    /**
     * @test
     */
    public function it_can_handle_guest_user()
    {
        Livewire::test(CommentForm::class)
            ->set('content', 'Guest comment')
            ->call('submit')
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_commentable_relationship()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommentForm::class)
            ->set('content', 'Test comment')
            ->set('commentable_type', 'App\Models\User')
            ->set('commentable_id', 999)  // Non-existent user
            ->call('submit')
            ->assertHasErrors(['commentable_id' => 'exists']);
    }
}
