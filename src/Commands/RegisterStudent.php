<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Commands;

use Wwwision\DCBExample\Types\StudentId;

/**
 * Commands to register a new student in the system
 */
final readonly class RegisterStudent implements Command
{
    public function __construct(
        public StudentId $studentId,
    ) {
    }
}
