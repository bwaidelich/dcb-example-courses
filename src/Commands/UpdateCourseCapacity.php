<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;

/**
 * Commands to change the total capacity of a course
 */
final readonly class UpdateCourseCapacity implements Command
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $newCapacity,
    ) {
    }
}
