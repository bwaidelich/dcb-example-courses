<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\StudentId;

/**
 * Commands to subscribe a student to a course
 */
final readonly class SubscribeStudentToCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public StudentId $studentId,
    ) {
    }
}
