<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse;

use Wwwision\DCBExample\Features\DefineCourse\Commands\RescheduleCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRescheduled;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBExample\Model\Course\CourseProjections;
use Wwwision\DCBExample\Model\Student\Dto\StudentIds;
use Wwwision\DCBTools\DomainEventAppender;
use Wwwision\DCBTools\Projection\ProjectionChain;

use function Wwwision\DCBTools\not;

final readonly class RescheduleCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $eventStore,
    ) {
    }

    public function __invoke(RescheduleCourse $command): void
    {
        $this->eventStore->appendWithState(
            stateProjection: ProjectionChain::start(
                CourseProjections::coursesWithConflictingSchedule($command->courseId, $command->newSchedule)
            )->then(
                CourseProjections::subscribedStudents(...)
            ),
            constraints: fn(StudentIds $affectedStudentIds) => [
                Course::exists($command->courseId),
                not(Course::hasMatchingSubscriptions($command->courseId, $affectedStudentIds)),
            ],
            onSuccess: fn () => new CourseRescheduled($command->courseId, $command->newSchedule),
        );
    }
}
