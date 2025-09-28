<?php

namespace Tests\Unit\Traits;

use App\Models\Comment;
use App\Models\User;
use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentableTraitTest extends TestCase
{
    use RefreshDatabase;

    private $commentableModel;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->commentableModel = new class () extends Model {
            use Commentable;

            protected $table = 'test_models';

            protected $fillable = ['name'];

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->table = 'users';  // Use existing table for testing
            }
        };

        $this->commentableModel->id = $this->user->id;
        $this->commentableModel->name = 'Test Model';
        $this->commentableModel->exists = true;
    }

    /**
     * @test
     */
    public function it_has_comments_relationship()
    {
        $relationship = $this->commentableModel->comments();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $relationship);
    }

    /**
     * @test
     */
    public function it_can_add_comment()
    {
        $commentText = 'This is a test comment';

        $comment = $this->commentableModel->addComment($commentText, $this->user->id);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals($commentText, $comment->content);
        $this->assertEquals($this->user->id, $comment->user_id);
    }

    /**
     * @test
     */
    public function it_can_get_comments_count()
    {
        Comment::factory()->count(3)->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
        ]);

        $count = $this->commentableModel->getCommentsCount();

        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_can_get_recent_comments()
    {
        Comment::factory()->count(5)->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
        ]);

        $recentComments = $this->commentableModel->getRecentComments(3);

        $this->assertCount(3, $recentComments);
    }

    /**
     * @test
     */
    public function it_can_check_if_has_comments()
    {
        $this->assertFalse($this->commentableModel->hasComments());

        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
        ]);

        $this->assertTrue($this->commentableModel->hasComments());
    }

    /**
     * @test
     */
    public function it_can_delete_all_comments()
    {
        Comment::factory()->count(3)->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
        ]);

        $this->assertEquals(3, $this->commentableModel->getCommentsCount());

        $result = $this->commentableModel->deleteAllComments();

        $this->assertTrue($result);
        $this->assertEquals(0, $this->commentableModel->getCommentsCount());
    }

    /**
     * @test
     */
    public function it_can_get_comments_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Comment::factory()->count(2)->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'user_id' => $user1->id,
        ]);

        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'user_id' => $user2->id,
        ]);

        $user1Comments = $this->commentableModel->getCommentsByUser($user1->id);

        $this->assertCount(2, $user1Comments);
        $this->assertTrue($user1Comments->every(fn ($comment) => $comment->user_id === $user1->id));
    }

    /**
     * @test
     */
    public function it_can_get_top_level_comments()
    {
        $parentComment = Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'parent_id' => null,
        ]);

        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'parent_id' => $parentComment->id,
        ]);

        $topLevelComments = $this->commentableModel->getTopLevelComments();

        $this->assertCount(1, $topLevelComments);
        $this->assertNull($topLevelComments->first()->parent_id);
    }

    /**
     * @test
     */
    public function it_can_get_comments_with_replies()
    {
        $parentComment = Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'parent_id' => null,
        ]);

        Comment::factory()->count(2)->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'parent_id' => $parentComment->id,
        ]);

        $commentsWithReplies = $this->commentableModel->getCommentsWithReplies();

        $this->assertCount(1, $commentsWithReplies);
        $this->assertTrue($commentsWithReplies->first()->relationLoaded('replies'));
    }

    /**
     * @test
     */
    public function it_can_get_approved_comments()
    {
        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'is_approved' => true,
        ]);

        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'is_approved' => false,
        ]);

        $approvedComments = $this->commentableModel->getApprovedComments();

        $this->assertCount(1, $approvedComments);
        $this->assertTrue($approvedComments->first()->is_approved);
    }

    /**
     * @test
     */
    public function it_can_get_pending_comments()
    {
        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'is_approved' => true,
        ]);

        Comment::factory()->create([
            'commentable_type' => get_class($this->commentableModel),
            'commentable_id' => $this->commentableModel->id,
            'is_approved' => false,
        ]);

        $pendingComments = $this->commentableModel->getPendingComments();

        $this->assertCount(1, $pendingComments);
        $this->assertFalse($pendingComments->first()->is_approved);
    }
}
