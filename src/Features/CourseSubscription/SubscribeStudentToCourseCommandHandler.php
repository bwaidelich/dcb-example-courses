<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\CourseSubscription;

use Wwwision\DCBExample\Features\CourseSubscription\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;
use Wwwision\DCBExample\Model\Student\StudentDecisionModels as Student;
use Wwwision\DCBExample\Model\Student\StudentProjections;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class SubscribeStudentToCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $eventStore,
    ) {
    }

    public function __invoke(SubscribeStudentToCourse $command): void
    {
        $this->eventStore->appendWithState(
            stateProjection: StudentProjections::subscriptions($command->studentId),
            constraints: fn (CourseIds $studentSubscriptions) => [
                Course::exists($command->courseId),
                Student::isRegistered($command->studentId),
                Course::hasFreeSeats($command->courseId),
                not(Student::isSubscribedToCourse($command->studentId, $command->courseId)),
                Student::numberOfSubscriptionsIsBelowLimit($command->studentId),
                Course::hasNoScheduleConflicts($command->courseId, $studentSubscriptions),
            ],
            onSuccess: fn () => new StudentSubscribedToCourse($command->studentId, $command->courseId),
        );
    }
}
