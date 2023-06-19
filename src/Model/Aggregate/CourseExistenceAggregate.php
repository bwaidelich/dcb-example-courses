<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainId;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Model\CourseId;

/**
 * Event-sourced aggregate enforcing domain rules concerning the existence of a specific course
 */
final class CourseExistenceAggregate implements Aggregate
{
    private bool $courseExists = false;


    public function __construct(
        private readonly CourseId $courseId,
    ) {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->courseExists = match ($domainEvent::class) {
            CourseCreated::class => true,
            default => $this->courseExists,
        };
    }

    public function courseExists(): bool
    {
        return $this->courseExists;
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated');
    }

    public function domainIds(): DomainId
    {
        return $this->courseId;
    }
}
