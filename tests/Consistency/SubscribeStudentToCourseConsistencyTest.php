<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\CourseSubscription\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\SubscribeStudentToCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRescheduled;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;

#[CoversClass(SubscribeStudentToCourseCommandHandler::class)]
final class SubscribeStudentToCourseConsistencyTest extends ConsistencyTestCase
{
    private const array MORNING = ['start' => '2026-08-01 10:00:00', 'end' => '2026-08-01 12:00:00']; // c1
    private const array AFTERNOON = ['start' => '2026-08-01 14:00:00', 'end' => '2026-08-01 16:00:00']; // c2 (no overlap with morning)
    private const array MIDDAY = ['start' => '2026-08-01 11:00:00', 'end' => '2026-08-01 13:00:00']; // overlaps morning

    private SubscribeStudentToCourseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new SubscribeStudentToCourseCommandHandler($this->tester->appender());
    }

    public function testSubscribingARegisteredStudentToAnExistingCourseIsAccepted(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class), $this->tester->build(StudentRegistered::class))
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->then($this->tester->build(StudentSubscribedToCourse::class))
            ->thenBoundaryIsBounded();
    }

    public function testSubscribingToANonExistentCourseIsRejected(): void
    {
        $this->tester->given($this->tester->build(StudentRegistered::class))
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->thenFails('courseExists');
    }

    public function testSubscribingAnUnregisteredStudentIsRejected(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class))
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->thenFails('studentIsRegistered');
    }

    public function testSubscribingToAFullCourseIsRejected(): void
    {
        $this->tester->given(
            $this->tester->build(CourseDefined::class, initialCapacity: 1),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentRegistered::class, studentId: 's2'),
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's2'),   // the only seat is taken
        )
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->thenFails('courseHasCapacity');
    }

    public function testConcurrentSubscriptionFillingTheLastSeatConflicts(): void
    {
        // Safety: capacity must not be exceeded when the last seat is taken concurrently.
        $this->tester->given(
            $this->tester->build(CourseDefined::class, initialCapacity: 1),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentRegistered::class, studentId: 's2'),
        )
            ->race($this->tester->build(StudentSubscribedToCourse::class, studentId: 's2'))   // fills the only seat between read and append
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->thenConflicts();
    }

    public function testConcurrentDuplicateSubscriptionConflicts(): void
    {
        // Safety: the same student must not be subscribed to the same course twice concurrently.
        $this->tester->given($this->tester->build(CourseDefined::class), $this->tester->build(StudentRegistered::class))
            ->race($this->tester->build(StudentSubscribedToCourse::class))
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class)))
            ->thenConflicts();
    }

    public function testScheduleConflictIntroducedByARescheduleIsDetected(): void
    {
        // c2 is rescheduled to overlap c1 (which s1 attends), so subscribing s1 to c2 must be rejected.
        $this->tester->given(
            $this->tester->build(CourseDefined::class, schedule: self::MORNING),
            $this->tester->build(CourseDefined::class, courseId: 'c2', schedule: self::AFTERNOON),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentSubscribedToCourse::class),   // s1 -> c1
            $this->tester->build(CourseRescheduled::class, courseId: 'c2', newSchedule: self::MIDDAY),   // c2 now overlaps c1
        )
            ->when(fn() => ($this->handler)($this->tester->build(SubscribeStudentToCourse::class, courseId: 'c2')))
            ->thenFails('hasNoScheduleConflicts');
    }
}
