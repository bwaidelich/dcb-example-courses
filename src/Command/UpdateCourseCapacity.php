<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;

/**
 * Command to change the total capacity of a course
 */
final readonly class UpdateCourseCapacity implements Command
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $newCapacity,
    ) {
    }
}
