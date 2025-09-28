<?php

namespace Tests\Unit\Traits;

use App\Traits\ReferenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceNumberTraitTest extends TestCase
{
    use RefreshDatabase;

    private $referenceModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referenceModel = new class () extends Model {
            use ReferenceNumber;

            protected $table = 'users';

            protected $fillable = ['name', 'reference_number'];
        };
    }

    /**
     * @test
     */
    public function it_generates_reference_number_on_creating()
    {
        $model = new $this->referenceModel();
        $model->name = 'Test Model';
        $model->save();

        $this->assertNotNull($model->reference_number);
        $this->assertMatchesRegularExpression('/^REF-\d{8}-\d{4}$/', $model->reference_number);
    }

    /**
     * @test
     */
    public function it_does_not_override_existing_reference_number()
    {
        $existingRef = 'CUSTOM-REF-123';

        $model = new $this->referenceModel();
        $model->name = 'Test Model';
        $model->reference_number = $existingRef;
        $model->save();

        $this->assertEquals($existingRef, $model->reference_number);
    }

    /**
     * @test
     */
    public function it_generates_unique_reference_numbers()
    {
        $model1 = new $this->referenceModel();
        $model1->name = 'Test Model 1';
        $model1->save();

        $model2 = new $this->referenceModel();
        $model2->name = 'Test Model 2';
        $model2->save();

        $this->assertNotEquals($model1->reference_number, $model2->reference_number);
    }

    /**
     * @test
     */
    public function it_can_generate_reference_with_custom_prefix()
    {
        $prefix = 'CUSTOM';
        $reference = $this->referenceModel->generateReference($prefix);

        $this->assertStringStartsWith($prefix.'-', $reference);
        $this->assertMatchesRegularExpression('/^CUSTOM-\d{8}-\d{4}$/', $reference);
    }

    /**
     * @test
     */
    public function it_can_generate_reference_with_default_prefix()
    {
        $reference = $this->referenceModel->generateReference();

        $this->assertStringStartsWith('REF-', $reference);
        $this->assertMatchesRegularExpression('/^REF-\d{8}-\d{4}$/', $reference);
    }

    /**
     * @test
     */
    public function it_includes_current_date_in_reference()
    {
        $reference = $this->referenceModel->generateReference();
        $currentDate = now()->format('Ymd');

        $this->assertStringContainsString($currentDate, $reference);
    }

    /**
     * @test
     */
    public function it_includes_random_number_in_reference()
    {
        $reference = $this->referenceModel->generateReference();

        // Extract the random part (last 4 digits)
        $parts = explode('-', $reference);
        $randomPart = end($parts);

        $this->assertEquals(4, strlen($randomPart));
        $this->assertIsNumeric($randomPart);
    }

    /**
     * @test
     */
    public function it_can_find_by_reference_number()
    {
        $model = new $this->referenceModel();
        $model->name = 'Test Model';
        $model->save();

        $foundModel = $this->referenceModel->findByReference($model->reference_number);

        $this->assertNotNull($foundModel);
        $this->assertEquals($model->id, $foundModel->id);
        $this->assertEquals($model->reference_number, $foundModel->reference_number);
    }

    /**
     * @test
     */
    public function it_returns_null_for_non_existent_reference()
    {
        $foundModel = $this->referenceModel->findByReference('NON-EXISTENT-REF');

        $this->assertNull($foundModel);
    }

    /**
     * @test
     */
    public function it_can_check_if_reference_exists()
    {
        $model = new $this->referenceModel();
        $model->name = 'Test Model';
        $model->save();

        $exists = $this->referenceModel->referenceExists($model->reference_number);
        $notExists = $this->referenceModel->referenceExists('NON-EXISTENT-REF');

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    /**
     * @test
     */
    public function it_can_get_reference_prefix()
    {
        $reference = 'CUSTOM-20231201-1234';
        $prefix = $this->referenceModel->getReferencePrefix($reference);

        $this->assertEquals('CUSTOM', $prefix);
    }

    /**
     * @test
     */
    public function it_can_get_reference_date()
    {
        $reference = 'REF-20231201-1234';
        $date = $this->referenceModel->getReferenceDate($reference);

        $this->assertEquals('20231201', $date);
    }

    /**
     * @test
     */
    public function it_can_get_reference_number_part()
    {
        $reference = 'REF-20231201-1234';
        $number = $this->referenceModel->getReferenceNumber($reference);

        $this->assertEquals('1234', $number);
    }

    /**
     * @test
     */
    public function it_can_validate_reference_format()
    {
        $validReferences = [
            'REF-20231201-1234',
            'CUSTOM-20231201-9999',
            'ABC-20231201-0001',
        ];

        $invalidReferences = [
            'INVALID-FORMAT',
            'REF-2023-1234',
            'REF-20231201-12345',
            'REF-20231301-1234',  // Invalid date
            '',
        ];

        foreach ($validReferences as $reference) {
            $this->assertTrue($this->referenceModel->isValidReferenceFormat($reference));
        }

        foreach ($invalidReferences as $reference) {
            $this->assertFalse($this->referenceModel->isValidReferenceFormat($reference));
        }
    }

    /**
     * @test
     */
    public function it_can_generate_reference_with_specific_date()
    {
        $specificDate = '2023-06-15';
        $reference = $this->referenceModel->generateReferenceWithDate('TEST', $specificDate);

        $this->assertStringContainsString('20230615', $reference);
        $this->assertStringStartsWith('TEST-', $reference);
    }

    /**
     * @test
     */
    public function it_can_get_references_by_date()
    {
        $date = now()->format('Y-m-d');

        // Create models with references from today
        $model1 = new $this->referenceModel();
        $model1->name = 'Test Model 1';
        $model1->save();

        $model2 = new $this->referenceModel();
        $model2->name = 'Test Model 2';
        $model2->save();

        $references = $this->referenceModel->getReferencesByDate($date);

        $this->assertCount(2, $references);
    }

    /**
     * @test
     */
    public function it_can_get_references_by_prefix()
    {
        $model1 = new $this->referenceModel();
        $model1->name = 'Test Model 1';
        $model1->reference_number = 'CUSTOM-20231201-1234';
        $model1->save();

        $model2 = new $this->referenceModel();
        $model2->name = 'Test Model 2';
        $model2->reference_number = 'CUSTOM-20231201-5678';
        $model2->save();

        $model3 = new $this->referenceModel();
        $model3->name = 'Test Model 3';
        $model3->save();  // Will get default REF prefix

        $customReferences = $this->referenceModel->getReferencesByPrefix('CUSTOM');
        $refReferences = $this->referenceModel->getReferencesByPrefix('REF');

        $this->assertCount(2, $customReferences);
        $this->assertCount(1, $refReferences);
    }

    /**
     * @test
     */
    public function it_can_get_latest_reference_number()
    {
        $model1 = new $this->referenceModel();
        $model1->name = 'Test Model 1';
        $model1->save();

        sleep(1);  // Ensure different timestamps

        $model2 = new $this->referenceModel();
        $model2->name = 'Test Model 2';
        $model2->save();

        $latestReference = $this->referenceModel->getLatestReference();

        $this->assertEquals($model2->reference_number, $latestReference->reference_number);
    }

    /**
     * @test
     */
    public function it_can_count_references_by_date()
    {
        $date = now()->format('Y-m-d');

        $model1 = new $this->referenceModel();
        $model1->name = 'Test Model 1';
        $model1->save();

        $model2 = new $this->referenceModel();
        $model2->name = 'Test Model 2';
        $model2->save();

        $count = $this->referenceModel->countReferencesByDate($date);

        $this->assertEquals(2, $count);
    }

    /**
     * @test
     */
    public function it_handles_edge_cases_for_reference_generation()
    {
        // Test with empty prefix
        $reference = $this->referenceModel->generateReference('');
        $this->assertStringStartsWith('-', $reference);

        // Test with very long prefix
        $longPrefix = str_repeat('A', 50);
        $reference = $this->referenceModel->generateReference($longPrefix);
        $this->assertStringStartsWith($longPrefix.'-', $reference);

        // Test with special characters in prefix
        $specialPrefix = 'TEST@#$';
        $reference = $this->referenceModel->generateReference($specialPrefix);
        $this->assertStringStartsWith($specialPrefix.'-', $reference);
    }
}
