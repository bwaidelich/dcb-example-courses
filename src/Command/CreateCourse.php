<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;

/**
 * Command to create a new course
 */
final readonly class CreateCourse implements Command
{
    private function __construct(
        public CourseId $courseId,
        public CourseCapacity $initialCapacity,
        public CourseTitle $courseTitle,
    ) {
    }

    public static function create(
        CourseId|string $courseId,
        CourseCapacity|int $initialCapacity,
        CourseTitle|string $courseTitle,
    ): self {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_int($initialCapacity)) {
            $initialCapacity = CourseCapacity::fromInteger($initialCapacity);
        }
        if (is_string($courseTitle)) {
            $courseTitle = CourseTitle::fromString($courseTitle);
        }
        return new self($courseId, $initialCapacity, $courseTitle);
    }
}
