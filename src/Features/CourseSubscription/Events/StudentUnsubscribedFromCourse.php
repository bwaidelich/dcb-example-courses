<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\CourseSubscription\Events;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBTools\Event\DomainEvent;

/**
 * Domain Event that occurs when a student was unsubscribed from a course
 *
 * Note: This event affects two entities (course and student)!
 */
final readonly class StudentUnsubscribedFromCourse implements DomainEvent
{
    public StudentId $studentId;
    public CourseId $courseId;

    public function __construct(
        StudentId|string $studentId,
        CourseId|string $courseId,
    ) {
        if (is_string($studentId)) {
            $studentId = StudentId::fromString($studentId);
        }
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        $this->studentId = $studentId;
        $this->courseId = $courseId;
    }
}
