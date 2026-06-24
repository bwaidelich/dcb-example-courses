<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RescheduleCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRescheduled;
use Wwwision\DCBExample\Features\DefineCourse\RescheduleCourseCommandHandler;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;

#[CoversClass(RescheduleCourseCommandHandler::class)]
final class RescheduleCourseConsistencyTest extends ConsistencyTestCase
{
    private const array MORNING = ['start' => '2026-08-01 10:00:00', 'end' => '2026-08-01 12:00:00'];
    private const array EARLY = ['start' => '2026-08-01 08:00:00', 'end' => '2026-08-01 09:00:00'];
    private const array FAR = ['start' => '2026-08-01 20:00:00', 'end' => '2026-08-01 21:00:00'];
    private const array AFTERNOON = ['start' => '2026-08-01 14:00:00', 'end' => '2026-08-01 16:00:00'];
    private const array LATE_AFTERNOON = ['start' => '2026-08-01 14:30:00', 'end' => '2026-08-01 16:30:00'];
    private const array OVERLAPS_AFTERNOON = ['start' => '2026-08-01 15:00:00', 'end' => '2026-08-01 17:00:00'];

    private RescheduleCourseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new RescheduleCourseCommandHandler($this->tester->appender());
    }

    public function testReschedulingACourseWithoutConflictsIsAccepted(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class, schedule: self::MORNING))
            ->when(fn() => ($this->handler)($this->tester->build(RescheduleCourse::class, newSchedule: self::EARLY)))
            ->then($this->tester->build(CourseRescheduled::class, newSchedule: self::EARLY));
    }

    public function testReschedulingIntoASlotThatClashesForASharedStudentIsRejected(): void
    {
        // s1 attends c1 and c2; moving c1 onto c2's slot would clash for s1 → rejected (single conflicting course).
        $this->tester->given(
            $this->tester->build(CourseDefined::class, schedule: self::MORNING),
            $this->tester->build(CourseDefined::class, courseId: 'c2', schedule: self::AFTERNOON),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentSubscribedToCourse::class),
            $this->tester->build(StudentSubscribedToCourse::class, courseId: 'c2'),
        )
            ->when(fn() => ($this->handler)($this->tester->build(RescheduleCourse::class, newSchedule: self::OVERLAPS_AFTERNOON)))
            ->thenFails('notHasMatchingSubscriptions');
    }

    /**
     * ⚠️ KNOWN BUG (detected by the harness): the reschedule consistency boundary is UNBOUNDED.
     *
     * `coursesWithConflictingSchedule` projects over empty tags, so its query — and therefore the AppendCondition —
     * matches every `CourseDefined`/`CourseRescheduled` event of every course. A correct bounded fix needs a
     * candidate-by-time-bucket-then-verify redesign (schedules are mutable, so naive slot tagging over-rejects).
     */
    public function testRescheduleConsistencyBoundaryIsUnbounded(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class, schedule: self::MORNING))
            ->when(fn() => ($this->handler)($this->tester->build(RescheduleCourse::class, newSchedule: self::EARLY)))
            ->then($this->tester->build(CourseRescheduled::class, newSchedule: self::EARLY))
            ->thenBoundaryIsUnbounded();
    }

    /**
     * ⚠️ KNOWN BUG (characterization test): because the boundary is unbounded, defining a completely unrelated,
     * non-overlapping course concurrently FALSELY conflicts with this reschedule.
     *
     * Correct behaviour would be `then($this->tester->build(CourseRescheduled::class, newSchedule: self::EARLY))`.
     */
    public function testUnrelatedConcurrentCourseDefinitionFalselyConflicts(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class, schedule: self::MORNING))
            ->race($this->tester->build(CourseDefined::class, courseId: 'c2', schedule: self::FAR))   // unrelated, non-overlapping
            ->when(fn() => ($this->handler)($this->tester->build(RescheduleCourse::class, newSchedule: self::EARLY)))
            ->thenConflicts();   // BUG: should append CourseRescheduled; the global boundary turns an unrelated write into a conflict
    }

    public function testClashWithMultipleConflictingCoursesIsDetected(): void
    {
        // The new slot overlaps both c2 and c3; s1 attends c1 and c2, so the reschedule must be rejected even though
        // there is more than one conflicting course (regression test for the multi-course union of subscribed students).
        $this->tester->given(
            $this->tester->build(CourseDefined::class, schedule: self::MORNING),
            $this->tester->build(CourseDefined::class, courseId: 'c2', schedule: self::AFTERNOON),
            $this->tester->build(CourseDefined::class, courseId: 'c3', schedule: self::LATE_AFTERNOON),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentSubscribedToCourse::class),
            $this->tester->build(StudentSubscribedToCourse::class, courseId: 'c2'),
        )
            ->when(fn() => ($this->handler)($this->tester->build(RescheduleCourse::class, newSchedule: self::OVERLAPS_AFTERNOON)))
            ->thenFails('notHasMatchingSubscriptions');
    }
}
