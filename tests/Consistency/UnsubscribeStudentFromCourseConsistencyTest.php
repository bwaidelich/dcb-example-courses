<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\CourseSubscription\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\UnsubscribeStudentFromCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;

#[CoversClass(UnsubscribeStudentFromCourseCommandHandler::class)]
final class UnsubscribeStudentFromCourseConsistencyTest extends ConsistencyTestCase
{
    private UnsubscribeStudentFromCourseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new UnsubscribeStudentFromCourseCommandHandler($this->tester->appender());
    }

    public function testUnsubscribingASubscribedStudentIsAccepted(): void
    {
        $this->tester->given(
            $this->tester->build(CourseDefined::class),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentSubscribedToCourse::class),
        )
            ->when(fn() => ($this->handler)($this->tester->build(UnsubscribeStudentFromCourse::class)))
            ->then($this->tester->build(StudentUnsubscribedFromCourse::class))
            ->thenBoundaryIsBounded();
    }

    public function testUnsubscribingAStudentThatIsNotSubscribedIsRejected(): void
    {
        $this->tester->given(
            $this->tester->build(CourseDefined::class),
            $this->tester->build(StudentRegistered::class),
        )
            ->when(fn() => ($this->handler)($this->tester->build(UnsubscribeStudentFromCourse::class)))
            ->thenFails('studentSubscribedToCourse');
    }

    public function testConcurrentUnsubscribeOfTheSameSubscriptionConflicts(): void
    {
        // Safety: the same subscription must not be removed twice concurrently.
        $this->tester->given(
            $this->tester->build(CourseDefined::class),
            $this->tester->build(StudentRegistered::class),
            $this->tester->build(StudentSubscribedToCourse::class),
        )
            ->race($this->tester->build(StudentUnsubscribedFromCourse::class))
            ->when(fn() => ($this->handler)($this->tester->build(UnsubscribeStudentFromCourse::class)))
            ->thenConflicts();
    }
}
