<?php

namespace Tests\Unit\Utilities;

use Illuminate\Pagination\LengthAwarePaginator;
use LaraUtilX\Utilities\PaginationUtil;
use Tests\TestCase;

class PaginationUtilTest extends TestCase
{
    protected array $testData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testData = range(1, 100);
    }

    /**
     * @test
     */
    public function it_can_paginate_array_data()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_second_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, 2);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(2, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_last_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, 10);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(10, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_different_per_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 20, 1);

        $this->assertEquals(20, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(5, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_options()
    {
        $options = [
            'path' => '/api/players',
            'pageName' => 'page',
            'query' => ['filter' => 'active'],
        ];

        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('/api/players', $paginator->path());
        $this->assertEquals('page', $paginator->getPageName());
    }

    /**
     * @test
     */
    public function it_can_paginate_empty_array()
    {
        $paginator = PaginationUtil::paginate([], 10, 1);

        $this->assertEquals(0, $paginator->count());
        $this->assertEquals(0, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_single_item()
    {
        $data = [1];
        $paginator = PaginationUtil::paginate($data, 10, 1);

        $this->assertEquals(1, $paginator->count());
        $this->assertEquals(1, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_large_per_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 1000, 1);

        $this->assertEquals(100, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_small_per_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 1, 1);

        $this->assertEquals(1, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(100, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_zero_per_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 0, 1);

        $this->assertEquals(0, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_negative_per_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, -10, 1);

        $this->assertEquals(0, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_zero_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, 0);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_negative_page()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, -1);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_page_beyond_last()
    {
        $paginator = PaginationUtil::paginate($this->testData, 10, 20);

        $this->assertEquals(0, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
        $this->assertEquals(20, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_associative_array()
    {
        $data = [
            'item1' => 'value1',
            'item2' => 'value2',
            'item3' => 'value3',
        ];

        $paginator = PaginationUtil::paginate($data, 2, 1);

        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_nested_arrays()
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob'],
        ];

        $paginator = PaginationUtil::paginate($data, 2, 1);

        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_objects()
    {
        $data = [
            (object) ['id' => 1, 'name' => 'John'],
            (object) ['id' => 2, 'name' => 'Jane'],
            (object) ['id' => 3, 'name' => 'Bob'],
        ];

        $paginator = PaginationUtil::paginate($data, 2, 1);

        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_mixed_data_types()
    {
        $data = [
            'string',
            123,
            ['array' => 'value'],
            (object) ['object' => 'value'],
            null,
        ];

        $paginator = PaginationUtil::paginate($data, 2, 1);

        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(5, $paginator->total());
        $this->assertEquals(3, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_unicode_data()
    {
        $data = [
            '测试',
            'ключ',
            '🎮',
            'café',
            'naïve',
        ];

        $paginator = PaginationUtil::paginate($data, 2, 1);

        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(5, $paginator->total());
        $this->assertEquals(3, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_large_dataset()
    {
        $data = range(1, 10000);
        $paginator = PaginationUtil::paginate($data, 100, 1);

        $this->assertEquals(100, $paginator->count());
        $this->assertEquals(10000, $paginator->total());
        $this->assertEquals(100, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_path()
    {
        $options = ['path' => '/custom/path'];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('/custom/path', $paginator->path());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_page_name()
    {
        $options = ['pageName' => 'custom_page'];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom_page', $paginator->getPageName());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_query_parameters()
    {
        $options = ['query' => ['filter' => 'active', 'sort' => 'name']];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('filter', $paginator->query());
        $this->assertArrayHasKey('sort', $paginator->query());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_fragment()
    {
        $options = ['fragment' => 'results'];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('#results', $paginator->fragment());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_appends()
    {
        $options = ['appends' => ['custom' => 'value']];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->appends());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_on_each_side()
    {
        $options = ['onEachSide' => 2];
        $paginator = PaginationUtil::paginate($this->testData, 10, 5, $options);

        $this->assertEquals(2, $paginator->onEachSide());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_view()
    {
        $options = ['view' => 'custom.pagination'];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom.pagination', $paginator->view());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_view_data()
    {
        $options = ['viewData' => ['custom' => 'data']];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->viewData());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_links_view()
    {
        $options = ['linksView' => 'custom.links'];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom.links', $paginator->linksView());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_links_view_data()
    {
        $options = ['linksViewData' => ['custom' => 'data']];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->linksViewData());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_links_view_closure()
    {
        $options = ['linksView' => function ($paginator) {
            return 'custom.links';
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom.links', $paginator->linksView());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_links_view_data_closure()
    {
        $options = ['linksViewData' => function ($paginator) {
            return ['custom' => 'data'];
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->linksViewData());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_view_closure()
    {
        $options = ['view' => function ($paginator) {
            return 'custom.pagination';
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom.pagination', $paginator->view());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_view_data_closure()
    {
        $options = ['viewData' => function ($paginator) {
            return ['custom' => 'data'];
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->viewData());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_path_closure()
    {
        $options = ['path' => function ($paginator) {
            return '/custom/path';
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('/custom/path', $paginator->path());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_fragment_closure()
    {
        $options = ['fragment' => function ($paginator) {
            return '#results';
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('#results', $paginator->fragment());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_appends_closure()
    {
        $options = ['appends' => function ($paginator) {
            return ['custom' => 'value'];
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('custom', $paginator->appends());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_query_closure()
    {
        $options = ['query' => function ($paginator) {
            return ['filter' => 'active'];
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertArrayHasKey('filter', $paginator->query());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_page_name_closure()
    {
        $options = ['pageName' => function ($paginator) {
            return 'custom_page';
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 1, $options);

        $this->assertEquals('custom_page', $paginator->getPageName());
    }

    /**
     * @test
     */
    public function it_can_paginate_with_custom_on_each_side_closure()
    {
        $options = ['onEachSide' => function ($paginator) {
            return 3;
        }];
        $paginator = PaginationUtil::paginate($this->testData, 10, 5, $options);

        $this->assertEquals(3, $paginator->onEachSide());
    }
}
