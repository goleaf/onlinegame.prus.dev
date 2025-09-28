<?php

namespace Tests\Unit\Utilities;

use Illuminate\Support\Collection;
use LaraUtilX\Utilities\FilteringUtil;
use Tests\TestCase;

class FilteringUtilTest extends TestCase
{
    protected Collection $testCollection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testCollection = collect([
            ['id' => 1, 'name' => 'John', 'age' => 25, 'active' => true, 'score' => 100],
            ['id' => 2, 'name' => 'Jane', 'age' => 30, 'active' => false, 'score' => 150],
            ['id' => 3, 'name' => 'Bob', 'age' => 35, 'active' => true, 'score' => 200],
            ['id' => 4, 'name' => 'Alice', 'age' => 28, 'active' => true, 'score' => 75],
            ['id' => 5, 'name' => 'Charlie', 'age' => 22, 'active' => false, 'score' => 300],
        ]);
    }

    /**
     * @test
     */
    public function it_can_filter_by_equals()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', 'equals', 25);

        $this->assertCount(1, $filtered);
        $this->assertEquals('John', $filtered->first()['name']);
    }

    /**
     * @test
     */
    public function it_can_filter_by_not_equals()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', 'not_equals', 25);

        $this->assertCount(4, $filtered);
        $this->assertNotContains('John', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_greater_than()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', '>', 25);

        $this->assertCount(4, $filtered);
        $this->assertNotContains('John', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_less_than()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', '<', 30);

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Charlie', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_greater_than_or_equal()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', '>=', 30);

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_less_than_or_equal()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', '<=', 25);

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Charlie', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_contains()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'name', 'contains', 'a');

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_not_contains()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'name', 'not_contains', 'a');

        $this->assertCount(3, $filtered);
        $this->assertNotContains('Jane', $filtered->pluck('name'));
        $this->assertNotContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_starts_with()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'name', 'starts_with', 'J');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Jane', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_ends_with()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'name', 'ends_with', 'e');

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_in()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', 'in', [25, 30, 35]);

        $this->assertCount(3, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_not_in()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', 'not_in', [25, 30, 35]);

        $this->assertCount(2, $filtered);
        $this->assertContains('Alice', $filtered->pluck('name'));
        $this->assertContains('Charlie', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_between()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', 'between', [25, 30]);

        $this->assertCount(3, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_boolean_true()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'active', 'equals', true);

        $this->assertCount(3, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_boolean_false()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'active', 'equals', false);

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Charlie', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_null_values()
    {
        $collection = collect([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => null],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'email', 'equals', null);

        $this->assertCount(1, $filtered);
        $this->assertEquals('Jane', $filtered->first()['name']);
    }

    /**
     * @test
     */
    public function it_can_filter_by_not_null_values()
    {
        $collection = collect([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => null],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'email', 'not_equals', null);

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_empty_string()
    {
        $collection = collect([
            ['name' => 'John', 'description' => 'Active player'],
            ['name' => 'Jane', 'description' => ''],
            ['name' => 'Bob', 'description' => 'Experienced player'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'description', 'equals', '');

        $this->assertCount(1, $filtered);
        $this->assertEquals('Jane', $filtered->first()['name']);
    }

    /**
     * @test
     */
    public function it_can_filter_by_non_empty_string()
    {
        $collection = collect([
            ['name' => 'John', 'description' => 'Active player'],
            ['name' => 'Jane', 'description' => ''],
            ['name' => 'Bob', 'description' => 'Experienced player'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'description', 'not_equals', '');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_numeric_values()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'score', '>', 150);

        $this->assertCount(2, $filtered);
        $this->assertContains('Bob', $filtered->pluck('name'));
        $this->assertContains('Charlie', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_decimal_values()
    {
        $collection = collect([
            ['name' => 'John', 'rating' => 4.5],
            ['name' => 'Jane', 'rating' => 3.2],
            ['name' => 'Bob', 'rating' => 4.8],
        ]);

        $filtered = FilteringUtil::filter($collection, 'rating', '>', 4.0);

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_case_insensitive_strings()
    {
        $collection = collect([
            ['name' => 'John', 'role' => 'Admin'],
            ['name' => 'Jane', 'role' => 'user'],
            ['name' => 'Bob', 'role' => 'ADMIN'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'role', 'equals', 'admin');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_regex_pattern()
    {
        $collection = collect([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@test.org'],
            ['name' => 'Bob', 'email' => 'bob@example.net'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'email', 'regex', '/@example\./');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_date_values()
    {
        $collection = collect([
            ['name' => 'John', 'created_at' => '2023-01-01'],
            ['name' => 'Jane', 'created_at' => '2023-02-01'],
            ['name' => 'Bob', 'created_at' => '2023-03-01'],
        ]);

        $filtered = FilteringUtil::filter($collection, 'created_at', '>=', '2023-02-01');

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_array_values()
    {
        $collection = collect([
            ['name' => 'John', 'tags' => ['admin', 'moderator']],
            ['name' => 'Jane', 'tags' => ['user']],
            ['name' => 'Bob', 'tags' => ['admin', 'user']],
        ]);

        $filtered = FilteringUtil::filter($collection, 'tags', 'contains', 'admin');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_nested_array_values()
    {
        $collection = collect([
            ['name' => 'John', 'permissions' => ['read', 'write']],
            ['name' => 'Jane', 'permissions' => ['read']],
            ['name' => 'Bob', 'permissions' => ['write', 'delete']],
        ]);

        $filtered = FilteringUtil::filter($collection, 'permissions', 'contains', 'write');

        $this->assertCount(2, $filtered);
        $this->assertContains('John', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_multiple_conditions()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'age', '>', 25);
        $filtered = FilteringUtil::filter($filtered, 'active', 'equals', true);

        $this->assertCount(2, $filtered);
        $this->assertContains('Bob', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_filter_by_complex_conditions()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'score', '>', 100);
        $filtered = FilteringUtil::filter($filtered, 'age', '<', 35);

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Alice', $filtered->pluck('name'));
    }

    /**
     * @test
     */
    public function it_can_handle_empty_collection()
    {
        $emptyCollection = collect([]);
        $filtered = FilteringUtil::filter($emptyCollection, 'name', 'contains', 'test');

        $this->assertCount(0, $filtered);
    }

    /**
     * @test
     */
    public function it_can_handle_invalid_field()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'invalid_field', 'equals', 'test');

        $this->assertCount(0, $filtered);
    }

    /**
     * @test
     */
    public function it_can_handle_invalid_operator()
    {
        $filtered = FilteringUtil::filter($this->testCollection, 'name', 'invalid_operator', 'test');

        $this->assertCount(0, $filtered);
    }

    /**
     * @test
     */
    public function it_can_handle_null_values_in_collection()
    {
        $collection = collect([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => null],
            ['name' => 'Bob', 'age' => 35],
        ]);

        $filtered = FilteringUtil::filter($collection, 'age', '>', 25);

        $this->assertCount(1, $filtered);
        $this->assertEquals('Bob', $filtered->first()['name']);
    }

    /**
     * @test
     */
    public function it_can_handle_mixed_data_types()
    {
        $collection = collect([
            ['name' => 'John', 'value' => 100],
            ['name' => 'Jane', 'value' => '150'],
            ['name' => 'Bob', 'value' => 200],
        ]);

        $filtered = FilteringUtil::filter($collection, 'value', '>', 100);

        $this->assertCount(2, $filtered);
        $this->assertContains('Jane', $filtered->pluck('name'));
        $this->assertContains('Bob', $filtered->pluck('name'));
    }
}
