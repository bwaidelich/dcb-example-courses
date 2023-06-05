<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;

/**
 * Command to change the title of a course
 */
final readonly class RenameCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public CourseTitle $newCourseTitle,
    ) {
    }
}
