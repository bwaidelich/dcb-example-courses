<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;

/**
 * Command to change the title of a course
 */
final readonly class RenameCourse implements Command
{
    private function __construct(
        public CourseId $courseId,
        public CourseTitle $newCourseTitle,
    ) {
    }

    public static function create(
        CourseId|string $courseId,
        CourseTitle|string $newCourseTitle,
    ): self {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_string($newCourseTitle)) {
            $newCourseTitle = CourseTitle::fromString($newCourseTitle);
        }
        return new self($courseId, $newCourseTitle);
    }
}
