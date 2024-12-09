<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBExample\Event\DomainEvent;

/**
 * A dummy projection that has no side effects, mostly for testing purposes
 * @implements Projection<null>
 */
final class NullProjection implements Projection
{
    public function initialState(): mixed
    {
        return null;
    }

    public function apply(mixed $state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): mixed
    {
        return null;
    }
}
