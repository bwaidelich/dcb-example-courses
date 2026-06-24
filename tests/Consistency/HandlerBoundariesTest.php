<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Wwwision\DCBExample\Features\CourseSubscription\SubscribeStudentToCourseCommandHandler;
use Wwwision\DCBExample\Features\CourseSubscription\UnsubscribeStudentFromCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\ChangeCourseCapacityCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\DefineCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RenameCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RescheduleCourseCommandHandler;
use Wwwision\DCBExample\Features\RegisterStudent\RegisterStudentCommandHandler;
use Wwwision\DCBTools\Testing\BoundaryStatus;
use Wwwision\DCBTools\Testing\HandlerBoundaryScanner;

/**
 * Scans every command handler's consistency boundary straight from the code (one structurally-valid sample command each,
 * built from {@see CourseTestSupport::defaults()}), with no per-handler scenario.
 */
#[CoversNothing]
final class HandlerBoundariesTest extends TestCase
{
    private const array HANDLERS = [
        RegisterStudentCommandHandler::class,
        DefineCourseCommandHandler::class,
        RenameCourseCommandHandler::class,
        ChangeCourseCapacityCommandHandler::class,
        SubscribeStudentToCourseCommandHandler::class,
        UnsubscribeStudentFromCourseCommandHandler::class,
        RescheduleCourseCommandHandler::class,
    ];

    /**
     * ⚠️ KNOWN BUG: `RescheduleCourse` is the lone handler with an unbounded boundary (`coursesWithConflictingSchedule`
     * projects over empty tags). Once it is fixed, replace this with a plain `$report->assertAllBounded();`.
     */
    public function testRescheduleIsTheOnlyHandlerWithAnUnboundedBoundary(): void
    {
        $report = HandlerBoundaryScanner::create(CourseTestSupport::serializer(), CourseTestSupport::defaults())
            ->scan(self::HANDLERS);

        $unbounded = $report->withStatus(BoundaryStatus::Unbounded);
        self::assertCount(1, $unbounded);
        self::assertSame(RescheduleCourseCommandHandler::class, $unbounded[0]->handlerClass);
        self::assertContains('CourseDefined', $unbounded[0]->unboundedEventTypes);
        self::assertContains('CourseRescheduled', $unbounded[0]->unboundedEventTypes);
    }

    public function testEveryOtherHandlerIsBounded(): void
    {
        $report = HandlerBoundaryScanner::create(CourseTestSupport::serializer(), CourseTestSupport::defaults())
            ->scan(self::HANDLERS);

        $bounded = array_map(static fn($finding) => $finding->handlerClass, $report->withStatus(BoundaryStatus::Bounded));
        self::assertCount(6, $bounded);
        self::assertNotContains(RescheduleCourseCommandHandler::class, $bounded);
    }
}
