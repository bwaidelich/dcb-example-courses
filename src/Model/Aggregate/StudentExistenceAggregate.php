<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Aggregate\Aggregate;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBEventStore\Aggregate\AggregateTrait;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Model\StudentId;

use function sprintf;

/**
 * Event-sourced aggregate enforcing domain rules concerning the existence of a single student
 */
final class StudentExistenceAggregate implements Aggregate
{
    use AggregateTrait;

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

    public function registerStudent(): void
    {
        if ($this->studentExists()) {
            throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $this->studentId->value), 1684579300);
        }
        $this->record(new StudentRegistered($this->studentId));
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
