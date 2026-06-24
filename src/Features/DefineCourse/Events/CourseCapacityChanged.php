<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Events;

use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBTools\Event\DomainEvent;

/**
 * Domain Event that occurs when the total capacity of a course has changed
 */
final readonly class CourseCapacityChanged implements DomainEvent
{
    public CourseId $courseId;
    public CourseCapacity $newCapacity;

    public function __construct(
        CourseId|string $courseId,
        CourseCapacity|int $newCapacity,
    ) {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_int($newCapacity)) {
            $newCapacity = CourseCapacity::fromInteger($newCapacity);
        }
        $this->courseId = $courseId;
        $this->newCapacity = $newCapacity;
    }
}
