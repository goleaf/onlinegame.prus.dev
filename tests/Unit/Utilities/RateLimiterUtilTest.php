<?php

namespace Tests\Unit\Utilities;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class RateLimiterUtilTest extends TestCase
{
    private RateLimiterUtil $rateLimiterUtil;

    private Repository $cache;

    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(Repository::class);
        $this->rateLimiter = $this->createMock(RateLimiter::class);
        $this->rateLimiterUtil = new RateLimiterUtil($this->cache);
    }

    /**
     * @test
     */
    public function it_can_attempt_within_limit()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 10)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('test-key', 600);

        $result = $this->rateLimiterUtil->attempt('test-key', 10, 10);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_attempt_when_over_limit()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 10)
            ->willReturn(true);

        $this
            ->rateLimiter
            ->expects($this->never())
            ->method('hit');

        $result = $this->rateLimiterUtil->attempt('test-key', 10, 10);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_get_attempts_count()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('attempts')
            ->with('test-key')
            ->willReturn(5);

        $result = $this->rateLimiterUtil->attempts('test-key');

        $this->assertEquals(5, $result);
    }

    /**
     * @test
     */
    public function it_can_get_remaining_attempts()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('remaining')
            ->with('test-key', 10)
            ->willReturn(5);

        $result = $this->rateLimiterUtil->remaining('test-key', 10);

        $this->assertEquals(5, $result);
    }

    /**
     * @test
     */
    public function it_can_clear_attempts()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('clear')
            ->with('test-key');

        $this->rateLimiterUtil->clear('test-key');

        $this->assertTrue(true);  // Assertion passes if no exception is thrown
    }

    /**
     * @test
     */
    public function it_can_get_available_in_time()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('availableIn')
            ->with('test-key')
            ->willReturn(300);

        $result = $this->rateLimiterUtil->availableIn('test-key');

        $this->assertEquals(300, $result);
    }

    /**
     * @test
     */
    public function it_can_check_too_many_attempts()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 10)
            ->willReturn(true);

        $result = $this->rateLimiterUtil->tooManyAttempts('test-key', 10);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_hit_rate_limiter()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('test-key', 60)
            ->willReturn(1);

        $result = $this->rateLimiterUtil->hit('test-key');

        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function it_can_hit_rate_limiter_with_custom_decay()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('test-key', 120)
            ->willReturn(2);

        $result = $this->rateLimiterUtil->hit('test-key', 120);

        $this->assertEquals(2, $result);
    }

    /**
     * @test
     */
    public function it_can_get_rate_limiter_instance()
    {
        $result = $this->rateLimiterUtil->getRateLimiter();

        $this->assertInstanceOf(RateLimiter::class, $result);
    }

    /**
     * @test
     */
    public function it_converts_decay_minutes_to_seconds()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 5)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('test-key', 300);  // 5 minutes * 60 seconds

        $this->rateLimiterUtil->attempt('test-key', 5, 5);
    }

    /**
     * @test
     */
    public function it_handles_zero_max_attempts()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 0)
            ->willReturn(true);

        $this
            ->rateLimiter
            ->expects($this->never())
            ->method('hit');

        $result = $this->rateLimiterUtil->attempt('test-key', 0, 10);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_zero_decay_minutes()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('test-key', 10)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('test-key', 0);  // 0 minutes * 60 seconds

        $this->rateLimiterUtil->attempt('test-key', 10, 0);
    }

    /**
     * @test
     */
    public function it_handles_empty_key()
    {
        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with('', 10)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with('', 600);

        $result = $this->rateLimiterUtil->attempt('', 10, 10);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_key()
    {
        $key = 'user:123@example.com:action';

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with($key, 10)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with($key, 600);

        $result = $this->rateLimiterUtil->attempt($key, 10, 10);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_key()
    {
        $key = '用户:123:操作';

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('tooManyAttempts')
            ->with($key, 10)
            ->willReturn(false);

        $this
            ->rateLimiter
            ->expects($this->once())
            ->method('hit')
            ->with($key, 600);

        $result = $this->rateLimiterUtil->attempt($key, 10, 10);

        $this->assertTrue($result);
    }
}
