<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use RuntimeException;
use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria\EventTypesAndTagsCriterion;
use Wwwision\DCBEventStore\Types\Tag;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Event\DomainEvent;

/**
 * @template S
 * @implements Projection<S>
 */
final class TaggedProjection implements Projection, StreamCriteriaAware
{
    /**
     * @param Projection<S> $wrapped
     */
    private function __construct(
        private readonly Tags $tags,
        private readonly Projection $wrapped,
    ) {
    }

    /**
     * @template PS
     * @param Tags|Tag $tags
     * @param Projection<PS> $handlers
     * @return self<PS>
     */
    public static function create(Tags|Tag $tags, Projection $handlers): self
    {
        return new self(
            $tags instanceof Tag ? Tags::create($tags) : $tags,
            $handlers,
        );
    }

    /**
     * @return S
     */
    public function initialState(): mixed
    {
        return $this->wrapped->initialState();
    }

    /**
     * @param S $state
     * @return S
     */
    public function apply(mixed $state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): mixed
    {
        if (!$eventEnvelope->event->tags->containEvery($this->tags)) {
            return $state;
        }
        return $this->wrapped->apply($state, $domainEvent, $eventEnvelope);
    }

    public function getCriteria(): Criteria
    {
        $criteria = [];
        if ($this->wrapped instanceof StreamCriteriaAware) {
            foreach ($this->wrapped->getCriteria() as $criterion) {
                $criteria[] = EventTypesAndTagsCriterion::create(
                    eventTypes: $criterion->eventTypes,
                    tags: $criterion->tags !== null ? $criterion->tags->merge($this->tags) : $this->tags,
                );
            }
        } else {
            $criteria[] = EventTypesAndTagsCriterion::create(
                tags: $this->tags,
            );
        }
        return Criteria::fromArray($criteria);
    }
}
