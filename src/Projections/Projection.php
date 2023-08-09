<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projections;

use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Events\DomainEvent;

/**
 * Contract for an Events-Sourced projection
 *
 * @template S
 */
interface Projection
{
    public function apply(DomainEvent $domainEvent): void;

    public function tags(): Tags;

    public function eventTypes(): EventTypes;

    /**
     * @return S
     */
    public function initialState(): mixed;

    /**
     * @return S
     */
    public function getState(): mixed;
}
