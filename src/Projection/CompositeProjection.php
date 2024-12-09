<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use stdClass;
use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria;
use Wwwision\DCBExample\Event\DomainEvent;

/**
 * @template S of object
 * @implements Projection<S>
 */
final class CompositeProjection implements Projection, StreamCriteriaAware
{
    /**
     * @param array<string, Projection<mixed>> $projections
     */
    private function __construct(
        private readonly array $projections,
    ) {
    }

    /**
     * @param array<string, Projection> $projections
     */
    public static function create(array $projections): self // @phpstan-ignore-line TODO fix
    {
        return new self($projections);
    }


    /**
     * @return S
     */
    public function initialState(): object
    {
        $state = new stdClass();
        foreach ($this->projections as $projectionKey => $projection) {
            $state->{$projectionKey} = $projection->initialState();
        }
        return $state;  // @phpstan-ignore-line TODO fix
    }

    /**
     * @param S $state
     * @return S
     */
    public function apply(mixed $state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): object
    {
        foreach ($this->projections as $projectionKey => $projection) {
            if ($projection instanceof StreamCriteriaAware && !$projection->getCriteria()->hashes()->intersect($eventEnvelope->criterionHashes)) {
                continue;
            }
            $state->{$projectionKey} = $projection->apply($state->{$projectionKey}, $domainEvent, $eventEnvelope);
        }
        return $state;
    }

    public function getCriteria(): Criteria
    {
        $criteria = Criteria::create();
        foreach ($this->projections as $projection) {
            if ($projection instanceof StreamCriteriaAware) {
                $criteria = $criteria->merge($projection->getCriteria());
            }
        }
        return $criteria;
    }
}
