<?php

namespace Tests\Unit\Traits;

use App\Traits\QueryEnrichTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryEnrichTraitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_apply_where_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'name' => 'John',
            'email' => 'john@example.com',
        ];

        $result = $trait->applyWhereConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_in_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'id' => [1, 2, 3],
            'status' => ['active', 'pending'],
        ];

        $result = $trait->applyWhereInConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_between_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => ['2023-01-01', '2023-12-31'],
            'age' => [18, 65],
        ];

        $result = $trait->applyWhereBetweenConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_like_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'name' => 'John',
            'email' => 'example.com',
        ];

        $result = $trait->applyWhereLikeConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_null_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'deleted_at',
            'email_verified_at',
        ];

        $result = $trait->applyWhereNullConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_not_null_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'email_verified_at',
            'phone',
        ];

        $result = $trait->applyWhereNotNullConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_date_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => '2023-01-01',
            'updated_at' => '2023-12-31',
        ];

        $result = $trait->applyWhereDateConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_time_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => '12:00:00',
            'updated_at' => '18:30:00',
        ];

        $result = $trait->applyWhereTimeConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_year_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => 2023,
            'updated_at' => 2024,
        ];

        $result = $trait->applyWhereYearConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_month_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => 1,
            'updated_at' => 12,
        ];

        $result = $trait->applyWhereMonthConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_day_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'created_at' => 15,
            'updated_at' => 30,
        ];

        $result = $trait->applyWhereDayConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_column_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            ['created_at', '>', 'updated_at'],
            ['email_verified_at', '!=', 'deleted_at'],
        ];

        $result = $trait->applyWhereColumnConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_has_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'posts' => function ($query): void {
                $query->where('status', 'published');
            },
            'comments' => function ($query): void {
                $query->where('approved', true);
            },
        ];

        $result = $trait->applyWhereHasConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_doesnt_have_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'posts' => function ($query): void {
                $query->where('status', 'draft');
            },
            'comments' => function ($query): void {
                $query->where('spam', true);
            },
        ];

        $result = $trait->applyWhereDoesntHaveConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_exists_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'posts' => function ($query): void {
                $query->where('status', 'published');
            },
        ];

        $result = $trait->applyWhereExistsConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_doesnt_exist_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'posts' => function ($query): void {
                $query->where('status', 'draft');
            },
        ];

        $result = $trait->applyWhereDoesntExistConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_where_raw_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'LENGTH(name) > ?' => [5],
            'email LIKE ?' => ['%@example.com'],
        ];

        $result = $trait->applyWhereRawConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_order_by_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'name' => 'asc',
            'created_at' => 'desc',
        ];

        $result = $trait->applyOrderByConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_group_by_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'status',
            'created_at',
        ];

        $result = $trait->applyGroupByConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_having_conditions()
    {
        $trait = new class () {
            use QueryEnrichTrait;
        };

        $query = \App\Models\User::query();
        $conditions = [
            'COUNT(*)' => ['>', 1],
            'AVG(age)' => ['>', 25],
        ];

        $result = $trait->applyHavingConditions($query, $conditions);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }
}
