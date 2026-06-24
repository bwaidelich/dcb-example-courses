<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse;

use Wwwision\DCBExample\Features\DefineCourse\Commands\ChangeCourseCapacity;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class ChangeCourseCapacityCommandHandler
{
    public function __construct(
        private DomainEventAppender $eventStore,
    ) {
    }

    public function __invoke(ChangeCourseCapacity $command): void
    {
        $this->eventStore->append(
            constraints: [
                Course::exists($command->courseId),
                not(Course::capacityEquals($command->courseId, $command->newCapacity)),
                Course::numberOfSubscriptionsIsBelowCapacity($command->courseId, $command->newCapacity->value),
            ],
            onSuccess: static fn () => new CourseCapacityChanged($command->courseId, $command->newCapacity),
        );
    }
}
