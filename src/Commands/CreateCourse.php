<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;

/**
 * Commands to create a new course
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
