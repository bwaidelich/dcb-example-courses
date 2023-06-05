<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\StudentId;

/**
 * Command to subscribe a student to a course
 */
final readonly class SubscribeStudentToCourse implements Command
{
    public function __construct(
        public CourseId $courseId,
        public StudentId $studentId,
    ) {
    }
}
