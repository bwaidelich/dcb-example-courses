<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Command;

use Wwwision\DCBExample\Model\StudentId;

/**
 * Command to register a new student in the system
 */
final readonly class RegisterStudent implements Command
{
    public function __construct(
        public StudentId $studentId,
    ) {
    }
}
