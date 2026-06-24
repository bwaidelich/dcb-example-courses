<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\DefineCourse\ChangeCourseCapacityCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Commands\ChangeCourseCapacity;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;

#[CoversClass(ChangeCourseCapacityCommandHandler::class)]
final class ChangeCourseCapacityConsistencyTest extends ConsistencyTestCase
{
    private ChangeCourseCapacityCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ChangeCourseCapacityCommandHandler($this->tester->appender());
    }

    public function testChangingCapacityOfAnExistingCourseIsAccepted(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class))   // default capacity 10
            ->when(fn() => ($this->handler)($this->tester->build(ChangeCourseCapacity::class, newCapacity: 5)))
            ->then($this->tester->build(CourseCapacityChanged::class, newCapacity: 5))
            ->thenBoundaryIsBounded();
    }

    public function testChangingCapacityToTheCurrentValueIsRejected(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class))   // default capacity 10
            ->when(fn() => ($this->handler)($this->tester->build(ChangeCourseCapacity::class, newCapacity: 10)))
            ->thenFails('notCourseCapacityEquals');
    }

    public function testShrinkingCapacityBelowTheNumberOfSubscriptionsIsRejected(): void
    {
        $this->tester->given(
            $this->tester->build(CourseDefined::class),   // default capacity 10
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's1'),
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's2'),
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's3'),
        )
            ->when(fn() => ($this->handler)($this->tester->build(ChangeCourseCapacity::class, newCapacity: 2)))
            ->thenFails('numberOfCourseSubscriptionsIsBelowCapacity');
    }

    public function testConcurrentSubscriptionWhileShrinkingCapacityConflicts(): void
    {
        // Safety: shrinking capacity to exactly the current headcount must be rejected if a seat is concurrently taken.
        $this->tester->given(
            $this->tester->build(CourseDefined::class, initialCapacity: 3),
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's1'),
            $this->tester->build(StudentSubscribedToCourse::class, studentId: 's2'),
        )
            ->race($this->tester->build(StudentSubscribedToCourse::class, studentId: 's3'))   // a third seat taken between read and append
            ->when(fn() => ($this->handler)($this->tester->build(ChangeCourseCapacity::class, newCapacity: 2)))
            ->thenConflicts();
    }
}
