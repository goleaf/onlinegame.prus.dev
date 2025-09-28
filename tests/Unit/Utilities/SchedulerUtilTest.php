<?php

namespace Tests\Unit\Utilities;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\SchedulerUtil;
use Tests\TestCase;

class SchedulerUtilTest extends TestCase
{
    private SchedulerUtil $schedulerUtil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schedulerUtil = new SchedulerUtil();
    }

    /**
     * @test
     */
    public function it_can_get_schedule_summary()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('command')
            ->willReturn('test:command');

        $mockEvent
            ->expects($this->once())
            ->method('getExpression')
            ->willReturn('* * * * *');

        $mockEvent
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test command');

        $mockEvent
            ->expects($this->once())
            ->method('nextRunDate')
            ->willReturn(Carbon::now()->addMinute());

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('output')
            ->willReturn('/dev/null');

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled Events: '.print_r([$mockEvent], true));

        $result = $this->schedulerUtil->getScheduleSummary();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test:command', $result[0]['command']);
        $this->assertEquals('* * * * *', $result[0]['expression']);
        $this->assertEquals('Test command', $result[0]['description']);
    }

    /**
     * @test
     */
    public function it_can_check_has_overdue_tasks()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getNextRunDate')
            ->willReturn(Carbon::now()->subMinute());  // Overdue

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        $result = $this->schedulerUtil->hasOverdueTasks();

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_returns_false_when_no_overdue_tasks()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getNextRunDate')
            ->willReturn(Carbon::now()->addMinute());  // Not overdue

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        $result = $this->schedulerUtil->hasOverdueTasks();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_ignores_running_tasks_when_checking_overdue()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getNextRunDate')
            ->willReturn(Carbon::now()->subMinute());  // Overdue

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(true);  // But running

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        $result = $this->schedulerUtil->hasOverdueTasks();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_empty_schedule()
    {
        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([]);

        $this->app->instance(Schedule::class, $mockSchedule);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled Events: '.print_r([], true));

        $result = $this->schedulerUtil->getScheduleSummary();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function it_handles_multiple_events()
    {
        $mockEvent1 = $this->createMock(Event::class);
        $mockEvent1
            ->expects($this->once())
            ->method('getAttribute')
            ->with('command')
            ->willReturn('test:command1');

        $mockEvent1
            ->expects($this->once())
            ->method('getExpression')
            ->willReturn('* * * * *');

        $mockEvent1
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test command 1');

        $mockEvent1
            ->expects($this->once())
            ->method('nextRunDate')
            ->willReturn(Carbon::now()->addMinute());

        $mockEvent1
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockEvent1
            ->expects($this->once())
            ->method('getAttribute')
            ->with('output')
            ->willReturn('/dev/null');

        $mockEvent2 = $this->createMock(Event::class);
        $mockEvent2
            ->expects($this->once())
            ->method('getAttribute')
            ->with('command')
            ->willReturn('test:command2');

        $mockEvent2
            ->expects($this->once())
            ->method('getExpression')
            ->willReturn('0 0 * * *');

        $mockEvent2
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test command 2');

        $mockEvent2
            ->expects($this->once())
            ->method('nextRunDate')
            ->willReturn(Carbon::now()->addHour());

        $mockEvent2
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockEvent2
            ->expects($this->once())
            ->method('getAttribute')
            ->with('output')
            ->willReturn('/dev/null');

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent1, $mockEvent2]);

        $this->app->instance(Schedule::class, $mockSchedule);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled Events: '.print_r([$mockEvent1, $mockEvent2], true));

        $result = $this->schedulerUtil->getScheduleSummary();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('test:command1', $result[0]['command']);
        $this->assertEquals('test:command2', $result[1]['command']);
    }

    /**
     * @test
     */
    public function it_handles_events_with_null_values()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('command')
            ->willReturn(null);

        $mockEvent
            ->expects($this->once())
            ->method('getExpression')
            ->willReturn(null);

        $mockEvent
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn(null);

        $mockEvent
            ->expects($this->once())
            ->method('nextRunDate')
            ->willReturn(Carbon::now()->addMinute());

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('output')
            ->willReturn(null);

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled Events: '.print_r([$mockEvent], true));

        $result = $this->schedulerUtil->getScheduleSummary();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['command']);
        $this->assertNull($result[0]['expression']);
        $this->assertNull($result[0]['description']);
    }

    /**
     * @test
     */
    public function it_handles_events_with_complex_expressions()
    {
        $mockEvent = $this->createMock(Event::class);
        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('command')
            ->willReturn('test:complex');

        $mockEvent
            ->expects($this->once())
            ->method('getExpression')
            ->willReturn('0 2 * * 1-5');  // Every weekday at 2 AM

        $mockEvent
            ->expects($this->once())
            ->method('getDescription')
            ->willReturn('Complex scheduled task');

        $mockEvent
            ->expects($this->once())
            ->method('nextRunDate')
            ->willReturn(Carbon::now()->addDay());

        $mockEvent
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(false);

        $mockEvent
            ->expects($this->once())
            ->method('getAttribute')
            ->with('output')
            ->willReturn('/var/log/test.log');

        $mockSchedule = $this->createMock(Schedule::class);
        $mockSchedule
            ->expects($this->once())
            ->method('events')
            ->willReturn([$mockEvent]);

        $this->app->instance(Schedule::class, $mockSchedule);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled Events: '.print_r([$mockEvent], true));

        $result = $this->schedulerUtil->getScheduleSummary();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('0 2 * * 1-5', $result[0]['expression']);
        $this->assertEquals('Complex scheduled task', $result[0]['description']);
    }
}
