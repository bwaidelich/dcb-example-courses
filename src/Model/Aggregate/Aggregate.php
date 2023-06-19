<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainId;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;

/**
 * Contract for an Event-Sourced Aggregate
 */
interface Aggregate
{
    public function apply(DomainEvent $domainEvent): void;

    public function domainIds(): DomainId|DomainIds;

    public function eventTypes(): EventTypes;
}
