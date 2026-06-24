<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\RegisterStudent\Events;

use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBTools\Event\DomainEvent;

/**
 * Domain Event that occurs when a new student was registered in the system
 */
final readonly class StudentRegistered implements DomainEvent
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
