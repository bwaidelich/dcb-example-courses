<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\CourseSubscription;

use Wwwision\DCBExample\Features\CourseSubscription\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBExample\Model\Student\StudentDecisionModels as Student;
use Wwwision\DCBTools\DomainEventAppender;

final readonly class UnsubscribeStudentFromCourseCommandHandler
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
    ) {
    }

    public function __invoke(UnsubscribeStudentFromCourse $command): void
    {
        $this->domainEventAppender->append(
            constraints: [
                Course::exists($command->courseId),
                Student::isRegistered($command->studentId),
                Student::isSubscribedToCourse($command->studentId, $command->courseId),
            ],
            onSuccess: static fn () => new StudentUnsubscribedFromCourse($command->studentId, $command->courseId),
        );
    }
}
