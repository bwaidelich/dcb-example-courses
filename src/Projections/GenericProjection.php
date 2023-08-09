<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projections;

use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Events\DomainEvent;

/**
 * @template P of ProjectionLogic
 * @implements Projection<P<S>>
 */
final readonly class GenericProjection implements Projection
{
    /**
     * @param P $logic
     */
    public function __construct(
        private Tags $tags,
        private ProjectionLogic $logic,
    )
    {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->logic->apply($domainEvent);
    }

    public function eventTypes(): EventTypes
    {
        return $this->logic->eventTypes();
    }

    /**
     * @return S
     */

    public function initialState(): mixed
    {
        return $this->logic->initialState();
    }

    public function getState(): mixed
    {
        return $this->logic->getState();
    }

    public function tags(): Tags
    {
        return $this->tags;
    }
}