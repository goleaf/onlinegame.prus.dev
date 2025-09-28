<?php

namespace Tests\Unit\Traits;

use App\Models\User;
use App\Traits\Commenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommenterTraitTest extends TestCase
{
    use RefreshDatabase;

    private $commenterModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commenterModel = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
    }

    /**
     * @test
     */
    public function it_returns_comments_relationship()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $relationship = $commenter->comments();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    /**
     * @test
     */
    public function it_returns_approved_comments_relationship()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $relationship = $commenter->approvedComments();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relationship);
    }

    /**
     * @test
     */
    public function it_returns_comments_count()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $count = $commenter->getCommentsCount();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    /**
     * @test
     */
    public function it_returns_recent_comments()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $recentComments = $commenter->getRecentComments();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentComments);
        $this->assertCount(0, $recentComments);
    }

    /**
     * @test
     */
    public function it_returns_recent_comments_with_custom_limit()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $recentComments = $commenter->getRecentComments(5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentComments);
        $this->assertCount(0, $recentComments);
    }

    /**
     * @test
     */
    public function it_returns_can_comment()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $canComment = $commenter->canComment();

        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_checks_if_has_commented_on_model()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetUser);

        $this->assertFalse($hasCommented);
    }

    /**
     * @test
     */
    public function it_handles_zero_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 0;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_negative_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = -1;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_very_large_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 999999999;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_null_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = null;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_empty_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = '';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_boolean_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = true;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_array_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = [1, 2, 3];

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_object_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = (object) ['id' => 1];

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_json_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = '{"id": 1}';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempt_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = '<script>alert("xss")</script>';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempt_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = "'; DROP TABLE comments; --";

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_unicode_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = '测试ID';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 'ID with "quotes" & symbols!';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_very_long_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = str_repeat('a', 1000);

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_decimal_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 123.45;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_negative_decimal_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = -123.45;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_very_large_decimal_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 999999999.99;

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_string_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 'string_id';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_boolean_string_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 'true';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_array_string_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 'Array';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_object_string_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = 'stdClass';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_json_string_user_id()
    {
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = '{"id": 1}';

        $count = $commenter->getCommentsCount();
        $canComment = $commenter->canComment();

        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
        $this->assertTrue($canComment);
    }

    /**
     * @test
     */
    public function it_handles_recent_comments_with_zero_limit()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $recentComments = $commenter->getRecentComments(0);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentComments);
        $this->assertCount(0, $recentComments);
    }

    /**
     * @test
     */
    public function it_handles_recent_comments_with_negative_limit()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $recentComments = $commenter->getRecentComments(-5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentComments);
        $this->assertCount(0, $recentComments);
    }

    /**
     * @test
     */
    public function it_handles_recent_comments_with_very_large_limit()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $recentComments = $commenter->getRecentComments(999999);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentComments);
        $this->assertCount(0, $recentComments);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_null_model()
    {
        $user = User::factory()->create();
        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $this->expectException(\TypeError::class);
        $commenter->hasCommentedOn(null);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_model_without_id()
    {
        $user = User::factory()->create();
        $targetModel = new User();

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetModel);

        $this->assertFalse($hasCommented);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_model_with_zero_id()
    {
        $user = User::factory()->create();
        $targetModel = new User();
        $targetModel->id = 0;

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetModel);

        $this->assertFalse($hasCommented);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_model_with_negative_id()
    {
        $user = User::factory()->create();
        $targetModel = new User();
        $targetModel->id = -1;

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetModel);

        $this->assertFalse($hasCommented);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_model_with_very_large_id()
    {
        $user = User::factory()->create();
        $targetModel = new User();
        $targetModel->id = 999999999;

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetModel);

        $this->assertFalse($hasCommented);
    }

    /**
     * @test
     */
    public function it_handles_has_commented_on_with_model_with_string_id()
    {
        $user = User::factory()->create();
        $targetModel = new User();
        $targetModel->id = 'string_id';

        $commenter = new class () extends Model {
            use Commenter;

            protected $table = 'users';
        };
        $commenter->id = $user->id;

        $hasCommented = $commenter->hasCommentedOn($targetModel);

        $this->assertFalse($hasCommented);
    }
}
