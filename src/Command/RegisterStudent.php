<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Types\StudentId;

/**
 * Command to register a new student in the system
 */
final readonly class RegisterStudent implements Command
{
    private function __construct(
        public StudentId $studentId,
    ) {
    }

    public static function create(
        StudentId|string $studentId,
    ): self {
        if (is_string($studentId)) {
            $studentId = StudentId::fromString($studentId);
        }
        return new self($studentId);
    }
}
