<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Model\StudentId;

/**
 * Event-sourced aggregate enforcing domain rules concerning the existence of a single student
 */
final class StudentExistenceAggregate implements Aggregate
{
    private bool $studentExists = false;

    public function __construct(
        private readonly StudentId $studentId,
    ) {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->studentExists = match ($domainEvent::class) {
            StudentRegistered::class => true,
            default => $this->studentExists,
        };
    }

    public function studentExists(): bool
    {
        return $this->studentExists;
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->studentId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('StudentRegistered');
    }
}
