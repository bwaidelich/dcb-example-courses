<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\RegisterStudent\Commands;

use Wwwision\DCBExample\Model\Student\Dto\StudentId;

final readonly class RegisterStudent
{
    public StudentId $studentId;

    public function __construct(
        StudentId|string $studentId,
    ) {
        if (is_string($studentId)) {
            $studentId = StudentId::fromString($studentId);
        }
        $this->studentId = $studentId;
    }
}
