<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse;

use Wwwision\DCBExample\Features\DefineCourse\Commands\DefineCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class DefineCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
    ) {
    }

    public function __invoke(DefineCourse $command): void
    {
        $this->domainEventAppender->append(
            constraints: [
                not(Course::exists($command->courseId))
            ],
            onSuccess: static fn () => new CourseDefined($command->courseId, $command->initialCapacity, $command->courseTitle),
        );
    }
}
