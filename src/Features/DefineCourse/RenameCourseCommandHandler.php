<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse;

use Wwwision\DCBExample\Features\DefineCourse\Commands\RenameCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRenamed;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class RenameCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
    ) {
    }

    public function __invoke(RenameCourse $command): void
    {
        $this->domainEventAppender->append(
            constraints: [
                Course::exists($command->courseId),
                not(Course::titleEquals($command->courseId, $command->newTitle)),
            ],
            onSuccess: static fn () => new CourseRenamed($command->courseId, $command->newTitle),
        );
    }
}
