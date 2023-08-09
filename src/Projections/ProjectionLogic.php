<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projections;

use Closure;
use InvalidArgumentException;
use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBExample\Events\DomainEvent;

/**
 * @template S
 */
final class ProjectionLogic
{

    /**
     * @var S
     */
    private mixed $state;

    /**
     * @param S $initialState
     * @param array<class-string<DomainEvent>, Closure(S $state, DomainEvent $event): S> $mappers
     */
    public function __construct(
        private mixed $initialState,
        private array $mappers = [],
    )
    {
        $this->state = $this->initialState();
    }

    public function initialState(): mixed
    {
        return $this->initialState;
    }

    /**
     * @template T of DomainEvent
     * @param class-string<T> $eventType
     * @param Closure(S $state, T $event): S $closure
     * @return self<S>
     */
    public function when(string $eventType, Closure $closure): self
    {
        if (array_key_exists($eventType, $this->mappers)) {
            throw new InvalidArgumentException(sprintf('Event type "%s" is already handled by this projection logic', $eventType), 1690974513);
        }
        $mappers = $this->mappers;
        /** @var array<class-string<DomainEvent>, Closure(S $state, DomainEvent $event): S> $mappers */
        $mappers[$eventType] = $closure;
        return new self($this->initialState, $mappers);
    }

    /**
     * @param DomainEvent $event
     */
    public function apply(DomainEvent $event): void
    {
        $this->state = isset($this->mappers[$event::class]) ? $this->mappers[$event::class]($this->state, $event) : $this->state;
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings(...array_map(static fn($domainEventClassName) => substr($domainEventClassName, strrpos($domainEventClassName, '\\') + 1), array_keys($this->mappers)));
    }

    public function getState(): mixed
    {
        return $this->state;
    }
}
