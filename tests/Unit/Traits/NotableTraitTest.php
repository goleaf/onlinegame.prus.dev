<?php

namespace Tests\Unit\Traits;

use App\Models\Note;
use App\Models\User;
use App\Traits\Notable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotableTraitTest extends TestCase
{
    use RefreshDatabase;

    private $notableModel;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->notableModel = new class () extends Model {
            use Notable;

            protected $table = 'test_models';

            protected $fillable = ['name'];

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
                $this->table = 'users';  // Use existing table for testing
            }
        };

        $this->notableModel->id = $this->user->id;
        $this->notableModel->name = 'Test Model';
        $this->notableModel->exists = true;
    }

    /**
     * @test
     */
    public function it_has_notes_relationship()
    {
        $relationship = $this->notableModel->notes();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $relationship);
    }

    /**
     * @test
     */
    public function it_can_add_note()
    {
        $noteTitle = 'Test Note';
        $noteContent = 'This is a test note content';

        $note = $this->notableModel->addNote($noteTitle, $noteContent, $this->user->id);

        $this->assertInstanceOf(Note::class, $note);
        $this->assertEquals($noteTitle, $note->title);
        $this->assertEquals($noteContent, $note->content);
        $this->assertEquals($this->user->id, $note->user_id);
    }

    /**
     * @test
     */
    public function it_can_add_note_with_category()
    {
        $noteTitle = 'Categorized Note';
        $noteContent = 'This note has a category';
        $category = 'important';

        $note = $this->notableModel->addNote($noteTitle, $noteContent, $this->user->id, $category);

        $this->assertInstanceOf(Note::class, $note);
        $this->assertEquals($category, $note->category);
    }

    /**
     * @test
     */
    public function it_can_get_notes_count()
    {
        Note::factory()->count(3)->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
        ]);

        $count = $this->notableModel->getNotesCount();

        $this->assertEquals(3, $count);
    }

    /**
     * @test
     */
    public function it_can_get_recent_notes()
    {
        Note::factory()->count(5)->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
        ]);

        $recentNotes = $this->notableModel->getRecentNotes(3);

        $this->assertCount(3, $recentNotes);
    }

    /**
     * @test
     */
    public function it_can_check_if_has_notes()
    {
        $this->assertFalse($this->notableModel->hasNotes());

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
        ]);

        $this->assertTrue($this->notableModel->hasNotes());
    }

    /**
     * @test
     */
    public function it_can_delete_all_notes()
    {
        Note::factory()->count(3)->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
        ]);

        $this->assertEquals(3, $this->notableModel->getNotesCount());

        $result = $this->notableModel->deleteAllNotes();

        $this->assertTrue($result);
        $this->assertEquals(0, $this->notableModel->getNotesCount());
    }

    /**
     * @test
     */
    public function it_can_get_notes_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Note::factory()->count(2)->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'user_id' => $user1->id,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'user_id' => $user2->id,
        ]);

        $user1Notes = $this->notableModel->getNotesByUser($user1->id);

        $this->assertCount(2, $user1Notes);
        $this->assertTrue($user1Notes->every(fn ($note) => $note->user_id === $user1->id));
    }

    /**
     * @test
     */
    public function it_can_get_notes_by_category()
    {
        Note::factory()->count(2)->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'category' => 'important',
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'category' => 'general',
        ]);

        $importantNotes = $this->notableModel->getNotesByCategory('important');

        $this->assertCount(2, $importantNotes);
        $this->assertTrue($importantNotes->every(fn ($note) => $note->category === 'important'));
    }

    /**
     * @test
     */
    public function it_can_get_public_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_public' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_public' => false,
        ]);

        $publicNotes = $this->notableModel->getPublicNotes();

        $this->assertCount(1, $publicNotes);
        $this->assertTrue($publicNotes->first()->is_public);
    }

    /**
     * @test
     */
    public function it_can_get_private_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_public' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_public' => false,
        ]);

        $privateNotes = $this->notableModel->getPrivateNotes();

        $this->assertCount(1, $privateNotes);
        $this->assertFalse($privateNotes->first()->is_public);
    }

    /**
     * @test
     */
    public function it_can_get_pinned_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_pinned' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_pinned' => false,
        ]);

        $pinnedNotes = $this->notableModel->getPinnedNotes();

        $this->assertCount(1, $pinnedNotes);
        $this->assertTrue($pinnedNotes->first()->is_pinned);
    }

    /**
     * @test
     */
    public function it_can_search_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'title' => 'Important Meeting Notes',
            'content' => 'Discussion about project timeline',
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'title' => 'Shopping List',
            'content' => 'Milk, bread, eggs',
        ]);

        $searchResults = $this->notableModel->searchNotes('meeting');

        $this->assertCount(1, $searchResults);
        $this->assertStringContainsString('Meeting', $searchResults->first()->title);
    }

    /**
     * @test
     */
    public function it_can_get_notes_with_attachments()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'has_attachments' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'has_attachments' => false,
        ]);

        $notesWithAttachments = $this->notableModel->getNotesWithAttachments();

        $this->assertCount(1, $notesWithAttachments);
        $this->assertTrue($notesWithAttachments->first()->has_attachments);
    }

    /**
     * @test
     */
    public function it_can_get_archived_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_archived' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_archived' => false,
        ]);

        $archivedNotes = $this->notableModel->getArchivedNotes();

        $this->assertCount(1, $archivedNotes);
        $this->assertTrue($archivedNotes->first()->is_archived);
    }

    /**
     * @test
     */
    public function it_can_get_active_notes()
    {
        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_archived' => true,
        ]);

        Note::factory()->create([
            'notable_type' => get_class($this->notableModel),
            'notable_id' => $this->notableModel->id,
            'is_archived' => false,
        ]);

        $activeNotes = $this->notableModel->getActiveNotes();

        $this->assertCount(1, $activeNotes);
        $this->assertFalse($activeNotes->first()->is_archived);
    }
}
