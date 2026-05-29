<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\CourseSubscription;

use Wwwision\DCBExample\Features\CourseSubscription\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBExample\Model\Student\StudentDecisionModels as Student;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class SubscribeStudentToCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
    ) {
    }

    public function __invoke(SubscribeStudentToCourse $command): void
    {
        $this->domainEventAppender->append(
            constraints: [
                Course::exists($command->courseId),
                Student::isRegistered($command->studentId),
                Course::hasFreeSeats($command->courseId),
                not(Student::isSubscribedToCourse($command->studentId, $command->courseId)),
                Student::numberOfSubscriptionsIsBelowLimit($command->studentId),
            ],
            onSuccess: fn () => new StudentSubscribedToCourse($command->studentId, $command->courseId),
        );
    }
}
