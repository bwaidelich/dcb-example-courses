<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBLibrary\DomainEvent;

/**
 * Domain Events that occurs when the title of a course has changed
 */
final readonly class CourseRenamed implements DomainEvent
{
    public function __construct(
        public CourseId $courseId,
        public CourseTitle $newCourseTitle,
    ) {
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag());
    }
}
