<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\CourseSubscription\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;

final readonly class UnsubscribeStudentFromCourse
{
    public StudentId $studentId;
    public CourseId $courseId;

    public function __construct(
        StudentId|string $studentId,
        CourseId|string $courseId,
    ) {
        if (is_string($studentId)) {
            $studentId = StudentId::fromString($studentId);
        }
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        $this->studentId = $studentId;
        $this->courseId = $courseId;
    }
}
