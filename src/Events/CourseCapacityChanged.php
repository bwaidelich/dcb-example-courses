<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBLibrary\DomainEvent;

/**
 * Domain Events that occurs when the total capacity of a course has changed
 */
final readonly class CourseCapacityChanged implements DomainEvent
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $newCapacity,
    ) {
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag());
    }
}
