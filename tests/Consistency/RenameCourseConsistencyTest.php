<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RenameCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRenamed;
use Wwwision\DCBExample\Features\DefineCourse\RenameCourseCommandHandler;

#[CoversClass(RenameCourseCommandHandler::class)]
final class RenameCourseConsistencyTest extends ConsistencyTestCase
{
    private RenameCourseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new RenameCourseCommandHandler($this->tester->appender());
    }

    public function testRenamingAnExistingCourseIsAccepted(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class))
            ->when(fn() => ($this->handler)($this->tester->build(RenameCourse::class, newTitle: 'New title')))
            ->then($this->tester->build(CourseRenamed::class, newTitle: 'New title'))
            ->thenBoundaryIsBounded();
    }

    public function testRenamingANonExistentCourseIsRejected(): void
    {
        $this->tester->when(fn() => ($this->handler)($this->tester->build(RenameCourse::class, newTitle: 'New title')))
            ->thenFails('courseExists');
    }

    public function testRenamingToTheCurrentTitleIsRejected(): void
    {
        $this->tester->given($this->tester->build(CourseDefined::class, courseTitle: 'Same title'))
            ->when(fn() => ($this->handler)($this->tester->build(RenameCourse::class, newTitle: 'Same title')))
            ->thenFails('notCourseTitleEquals');
    }

    public function testConcurrentRenameOfTheSameCourseConflicts(): void
    {
        // Safety: two renames of the same course must not both succeed.
        $this->tester->given($this->tester->build(CourseDefined::class))
            ->race($this->tester->build(CourseRenamed::class, newTitle: 'Concurrent title'))
            ->when(fn() => ($this->handler)($this->tester->build(RenameCourse::class, newTitle: 'New title')))
            ->thenConflicts();
    }
}
