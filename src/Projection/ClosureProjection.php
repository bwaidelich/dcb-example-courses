<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria;
use Wwwision\DCBExample\Event\DomainEvent;

/**
 * @template S
 * @implements Projection<S>
 */
final class ClosureProjection implements Projection, StreamCriteriaAware
{
    /**
     * @param S $initialState
     * @param array<class-string, callable> $handlers
     */
    private function __construct(
        private readonly mixed $initialState,
        public readonly array $handlers,
        public readonly bool $onlyLastEvent,
    ) {
    }

    /**
     * @template PS
     * @param PS $initialState
     * @return self<PS>
     */
    public static function create(mixed $initialState, bool $onlyLastEvent = false): self
    {
        return new self($initialState, [], $onlyLastEvent);
    }

    /**
     * @template E of DomainEvent
     * @param class-string<E> $class
     * @param callable(S, E): S $cb
     * @return self<S>
     */
    public function when(string $class, callable $cb): self
    {
        return new self($this->initialState, [...$this->handlers, $class => $cb], $this->onlyLastEvent);
    }

    /**
     * @return S
     */
    public function initialState(): mixed
    {
        return $this->initialState;
    }

    /**
     * @param S $state
     * @return S
     */
    public function apply(mixed $state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): mixed
    {
        if (!array_key_exists($domainEvent::class, $this->handlers)) {
            return $state;
        }
        return $this->handlers[$domainEvent::class]($state, $domainEvent, $eventEnvelope);
    }

    public function getCriteria(): Criteria
    {
        $eventTypes = EventTypes::fromStrings(...array_map(static fn($domainEventClassName) => substr($domainEventClassName, strrpos($domainEventClassName, '\\') + 1), array_keys($this->handlers)));
        return Criteria::create(Criteria\EventTypesAndTagsCriterion::create(eventTypes: $eventTypes, onlyLastEvent: $this->onlyLastEvent));
    }
}
