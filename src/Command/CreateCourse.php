<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;

/**
 * Command to create a new course
 */
final readonly class CreateCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $initialCapacity,
        public CourseTitle $courseTitle,
    ) {
    }
}
