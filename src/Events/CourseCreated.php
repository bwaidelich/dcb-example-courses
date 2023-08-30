<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBLibrary\DomainEvent;

/**
 * Domain Events that occurs when a new course was created
 */
final readonly class CourseCreated implements DomainEvent
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $initialCapacity,
        public CourseTitle $courseTitle,
    ) {
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag());
    }
}
