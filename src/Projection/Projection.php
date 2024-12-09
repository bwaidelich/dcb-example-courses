<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBExample\Event\DomainEvent;

/**
 * @template S
 */
interface Projection
{
    /**
     * @return S
     */
    public function initialState(): mixed;

    /**
     * @param S $state
     * @return S
     */
    public function apply(mixed $state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): mixed;
}
