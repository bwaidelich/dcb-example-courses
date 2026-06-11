<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Wwwision\DCBExample\Features\CourseSubscription\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Features\CourseSubscription\SubscribeStudentToCourseCommandHandler;
use Wwwision\DCBExample\Features\CourseSubscription\UnsubscribeStudentFromCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\ChangeCourseCapacityCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Commands\ChangeCourseCapacity;
use Wwwision\DCBExample\Features\DefineCourse\Commands\DefineCourse;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RenameCourse;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RescheduleCourse;
use Wwwision\DCBExample\Features\DefineCourse\DefineCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RenameCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RescheduleCourseCommandHandler;
use Wwwision\DCBExample\Features\RegisterStudent\Commands\RegisterStudent;
use Wwwision\DCBExample\Features\RegisterStudent\RegisterStudentCommandHandler;
use Wwwision\DCBTools\DomainEventAppender;
use Wwwision\DCBTools\StateProjector;

/**
 * Main authority of this package, responsible to handle incoming commands
 */
final readonly class App
{
    public function __construct(
        private DomainEventAppender $domainEventAppender,
        private StateProjector $stateProjector,
    ) {
    }

    /**
     * @param array{start: string, end: string} $schedule
     */
    public function defineCourse(string $courseId, string $courseTitle, int $initialCapacity, array $schedule): void
    {
        $handler = new DefineCourseCommandHandler($this->domainEventAppender);
        $handler(new DefineCourse($courseId, $initialCapacity, $courseTitle, $schedule));
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

    public function rescheduleCourse(string $courseId, array $newSchedule): void
    {
        $handler = new RescheduleCourseCommandHandler($this->domainEventAppender);
        $handler(new RescheduleCourse($courseId, $newSchedule));
    }

    public function registerStudent(string $studentId): void
    {
        $handler = new RegisterStudentCommandHandler($this->domainEventAppender);
        $handler(new RegisterStudent($studentId));
    }

    public function subscribeStudentToCourse(string $studentId, string $courseId): void
    {
        $handler = new SubscribeStudentToCourseCommandHandler($this->domainEventAppender, $this->stateProjector);
        $handler(new SubscribeStudentToCourse($studentId, $courseId));
    }

    public function unsubscribeStudentFromCourse(string $studentId, string $courseId): void
    {
        $handler = new UnsubscribeStudentFromCourseCommandHandler($this->domainEventAppender);
        $handler(new UnsubscribeStudentFromCourse($studentId, $courseId));
    }
}
