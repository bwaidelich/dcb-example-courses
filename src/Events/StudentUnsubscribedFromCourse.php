<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\StudentId;
use Wwwision\DCBLibrary\DomainEvent;

/**
 * Domain Events that occurs when a student was unsubscribed from a course
 *
 * Note: This event affects two entities (course and student)!
 */
final readonly class StudentUnsubscribedFromCourse implements DomainEvent
{
    public function __construct(
        public StudentId $studentId,
        public CourseId $courseId,
    ) {
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag(), $this->studentId->toTag());
    }
}
