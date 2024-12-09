<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\StudentId;

/**
 * Command to subscribe a student to a course
 */
final readonly class SubscribeStudentToCourse implements Command
{
    private function __construct(
        public CourseId $courseId,
        public StudentId $studentId,
    ) {
    }

    public static function create(
        CourseId|string $courseId,
        StudentId|string $studentId,
    ): self {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_string($studentId)) {
            $studentId = StudentId::fromString($studentId);
        }
        return new self($courseId, $studentId);
    }
}
