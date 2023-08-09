<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\StudentId;

/**
 * Commands to unsubscribe a student from a course
 */
final readonly class UnsubscribeStudentFromCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public StudentId $studentId,
    ) {
    }
}
