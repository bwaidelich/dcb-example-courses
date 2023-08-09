<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;

/**
 * Commands to change the title of a course
 */
final readonly class RenameCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public CourseTitle $newCourseTitle,
    ) {
    }
}
