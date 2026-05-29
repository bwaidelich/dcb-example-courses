<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Wwwision\DCBExample\Features\CourseSubscription\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\SubscribeStudentToCourseCommandHandler;
use Wwwision\DCBExample\Features\CourseSubscription\UnsubscribeStudentFromCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\ChangeCourseCapacityCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Commands\ChangeCourseCapacity;
use Wwwision\DCBExample\Features\DefineCourse\Commands\DefineCourse;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RenameCourse;
use Wwwision\DCBExample\Features\DefineCourse\DefineCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRenamed;
use Wwwision\DCBExample\Features\DefineCourse\RenameCourseCommandHandler;
use Wwwision\DCBExample\Features\RegisterStudent\Commands\RegisterStudent;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;
use Wwwision\DCBExample\Features\RegisterStudent\RegisterStudentCommandHandler;
use Wwwision\DCBExample\Model\Course\CourseDecisionModels as Course;
use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBExample\Model\Student\StudentDecisionModels as Student;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

/**
 * Main authority of this package, responsible to handle incoming commands
 */
final readonly class App
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
    ) {
    }

    public function defineCourse(string $courseId, string $courseTitle, int $initialCapacity): void
    {
        $handler = new DefineCourseCommandHandler($this->domainEventAppender);
        $handler(new DefineCourse($courseId, $initialCapacity, $courseTitle));
    }

    public function renameCourse(string $courseId, string $newTitle): void
    {
        $handler = new RenameCourseCommandHandler($this->domainEventAppender);
        $handler(new RenameCourse($courseId, $newTitle));
    }

    public function changeCourseCapacity(string $courseId, int $newCapacity): void
    {
        $handler = new ChangeCourseCapacityCommandHandler($this->domainEventAppender);
        $handler(new ChangeCourseCapacity($courseId, $newCapacity));
    }

    public function registerStudent(string $studentId): void
    {
        $handler = new RegisterStudentCommandHandler($this->domainEventAppender);
        $handler(new RegisterStudent($studentId));
    }

    public function subscribeStudentToCourse(string $studentId, string $courseId): void
    {
        $handler = new SubscribeStudentToCourseCommandHandler($this->domainEventAppender);
        $handler(new SubscribeStudentToCourse($studentId, $courseId));
    }

    public function unsubscribeStudentFromCourse(string $studentId, string $courseId): void
    {
        $handler = new UnsubscribeStudentFromCourseCommandHandler($this->domainEventAppender);
        $handler(new UnsubscribeStudentFromCourse($studentId, $courseId));
    }
}
