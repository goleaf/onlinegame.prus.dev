<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment',
        ]);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals('Test comment', $comment->content);
        $this->assertEquals($user->id, $comment->user_id);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $comment = new Comment();
        $fillable = $comment->getFillable();

        $this->assertContains('commentable_id', $fillable);
        $this->assertContains('commentable_type', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('parent_id', $fillable);
        $this->assertContains('content', $fillable);
        $this->assertContains('is_approved', $fillable);
        $this->assertContains('is_pinned', $fillable);
        $this->assertContains('metadata', $fillable);
    }

    /**
     * @test
     */
    public function it_casts_attributes_correctly()
    {
        $comment = Comment::factory()->create();
        $casts = $comment->getCasts();

        $this->assertArrayHasKey('is_approved', $casts);
        $this->assertArrayHasKey('is_pinned', $casts);
        $this->assertArrayHasKey('metadata', $casts);
    }

    /**
     * @test
     */
    public function it_has_commentable_relationship()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $user->id,
            'commentable_type' => User::class,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $comment->commentable());
        $this->assertEquals($user->id, $comment->commentable->id);
    }

    /**
     * @test
     */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $comment->user());
        $this->assertEquals($user->id, $comment->user->id);
    }

    /**
     * @test
     */
    public function it_has_parent_relationship()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create(['user_id' => $user->id]);
        $childComment = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $childComment->parent());
        $this->assertEquals($parentComment->id, $childComment->parent->id);
    }

    /**
     * @test
     */
    public function it_has_replies_relationship()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create(['user_id' => $user->id]);
        $reply1 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);
        $reply2 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $parentComment->replies());
        $this->assertTrue($parentComment->replies->contains($reply1));
        $this->assertTrue($parentComment->replies->contains($reply2));
    }

    /**
     * @test
     */
    public function it_has_approved_replies_relationship()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create(['user_id' => $user->id]);
        $approvedReply = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
            'is_approved' => true,
        ]);
        $unapprovedReply = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
            'is_approved' => false,
        ]);

        $approvedReplies = $parentComment->approvedReplies;

        $this->assertTrue($approvedReplies->contains($approvedReply));
        $this->assertFalse($approvedReplies->contains($unapprovedReply));
    }

    /**
     * @test
     */
    public function it_can_scope_approved_comments()
    {
        $user = User::factory()->create();
        $approvedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'is_approved' => true,
        ]);
        $unapprovedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'is_approved' => false,
        ]);

        $approvedComments = Comment::approved()->get();

        $this->assertTrue($approvedComments->contains($approvedComment));
        $this->assertFalse($approvedComments->contains($unapprovedComment));
    }

    /**
     * @test
     */
    public function it_can_scope_pinned_comments()
    {
        $user = User::factory()->create();
        $pinnedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'is_pinned' => true,
        ]);
        $unpinnedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'is_pinned' => false,
        ]);

        $pinnedComments = Comment::pinned()->get();

        $this->assertTrue($pinnedComments->contains($pinnedComment));
        $this->assertFalse($pinnedComments->contains($unpinnedComment));
    }

    /**
     * @test
     */
    public function it_can_scope_top_level_comments()
    {
        $user = User::factory()->create();
        $topLevelComment = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => null,
        ]);
        $replyComment = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $topLevelComment->id,
        ]);

        $topLevelComments = Comment::topLevel()->get();

        $this->assertTrue($topLevelComments->contains($topLevelComment));
        $this->assertFalse($topLevelComments->contains($replyComment));
    }

    /**
     * @test
     */
    public function it_can_scope_for_model()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $user->id,
            'commentable_type' => User::class,
        ]);

        $modelComments = Comment::forModel($user)->get();

        $this->assertTrue($modelComments->contains($comment));
    }

    /**
     * @test
     */
    public function it_can_check_if_is_reply()
    {
        $user = User::factory()->create();
        $parentComment = Comment::factory()->create(['user_id' => $user->id]);
        $replyComment = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertFalse($parentComment->isReply());
        $this->assertTrue($replyComment->isReply());
    }

    /**
     * @test
     */
    public function it_can_get_depth()
    {
        $user = User::factory()->create();
        $level1 = Comment::factory()->create(['user_id' => $user->id]);
        $level2 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $level1->id,
        ]);
        $level3 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $level2->id,
        ]);

        $this->assertEquals(0, $level1->getDepth());
        $this->assertEquals(1, $level2->getDepth());
        $this->assertEquals(2, $level3->getDepth());
    }

    /**
     * @test
     */
    public function it_can_check_if_can_be_replied_to()
    {
        $user = User::factory()->create();
        $level1 = Comment::factory()->create(['user_id' => $user->id]);
        $level2 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $level1->id,
        ]);
        $level3 = Comment::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $level2->id,
        ]);

        $this->assertTrue($level1->canBeRepliedTo());
        $this->assertTrue($level2->canBeRepliedTo());
        $this->assertFalse($level3->canBeRepliedTo());
    }
}
