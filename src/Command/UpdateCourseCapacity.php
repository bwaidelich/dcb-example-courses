<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;

/**
 * Command to change the total capacity of a course
 */
final readonly class UpdateCourseCapacity implements Command
{
    private function __construct(
        public CourseId $courseId,
        public CourseCapacity $newCapacity,
    ) {
    }

    public static function create(
        CourseId|string $courseId,
        CourseCapacity|int $newCapacity,
    ): self {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_int($newCapacity)) {
            $newCapacity = CourseCapacity::fromInteger($newCapacity);
        }
        return new self($courseId, $newCapacity);
    }
}
