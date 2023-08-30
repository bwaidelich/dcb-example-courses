<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\StudentId;
use Wwwision\DCBLibrary\DomainEvent;

/**
 * Domain Events that occurs when a new student was registered in the system
 */
final readonly class StudentRegistered implements DomainEvent
{
    public function __construct(
        public StudentId $studentId,
    ) {
    }

    public function tags(): Tags
    {
        return Tags::create($this->studentId->toTag());
    }
}
