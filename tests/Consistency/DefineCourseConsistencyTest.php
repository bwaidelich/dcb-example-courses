<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\DefineCourse\Commands\DefineCourse;
use Wwwision\DCBExample\Features\DefineCourse\DefineCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;

#[CoversClass(DefineCourseCommandHandler::class)]
final class DefineCourseConsistencyTest extends ConsistencyTestCase
{
    private DefineCourseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new DefineCourseCommandHandler($this->tester->appender());
    }

    public function testDefiningANewCourseIsAccepted(): void
    {
        $this->tester->when(fn() => ($this->handler)($this->tester->build(DefineCourse::class)))
            ->then($this->tester->build(CourseDefined::class))
            ->thenBoundaryIsBounded();
    }

    public function testDefiningACourseWithAnExistingIdIsRejected(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class))
            ->when(fn() => ($this->handler)($this->tester->build(DefineCourse::class)))
            ->thenFails('notCourseExists');
    }

    public function testConcurrentDefinitionOfTheSameCourseConflicts(): void
    {
        $this->tester->race($this->tester->build(CourseDefined::class))
            ->when(fn() => ($this->handler)($this->tester->build(DefineCourse::class)))
            ->thenConflicts();
    }

    public function testConcurrentDefinitionOfADifferentCourseIsAccepted(): void
    {
        // No false contention: the boundary is scoped to course:c1.
        $this->tester->race($this->tester->build(CourseDefined::class, courseId: 'c2'))
            ->when(fn() => ($this->handler)($this->tester->build(DefineCourse::class)))
            ->then($this->tester->build(CourseDefined::class));
    }
}
