<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\Projection;

use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBEventStore\Event\Tags;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\Query\QueryItem;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBExample\Infrastructure\DomainEvent;
use Wwwision\DCBExample\Infrastructure\ProvidesTags;

/**
 * @template S
 *
 * @implements Projection<S>
 */
final class AtomicProjection implements Projection
{
    /**
     * @var array<class-string<DomainEvent>, S|callable>
     */
    private array $handlers = [];

    /**
     * @param S $state
     */
    private function __construct(
        private readonly Tags $tags,
        private mixed $state,
        private readonly bool $onlyLastEvent,
    ) {
    }

    /**
     * @template PS
     *
     * @param PS $initialState
     *
     * @return self<PS>
     */
    public static function create(Tag|Tags|ProvidesTags $tags, mixed $initialState, bool $onlyLastEvent = false): self
    {
        if ($tags instanceof ProvidesTags) {
            $tags = $tags->tags();
        }
        if ($tags instanceof Tag) {
            $tags = Tags::create($tags);
        }
        return new self($tags, $initialState, $onlyLastEvent);
    }

    /**
     * @template E of DomainEvent
     * @template SN
     *
     * @param class-string<E> $class
     * @param (callable(S|SN, E, SequencedEvent): SN)|(S|SN) $cb
     *
     * @return self<S|SN>
     */
    public function when(string $class, mixed $cb): self
    {
        $this->handlers[$class] = $cb; // @phpstan-ignore assign.propertyType

        return $this;
    }

    public function apply(DomainEvent $event, SequencedEvent $sequencedEvent): void
    {
        if (!array_key_exists($event::class, $this->handlers) || !$sequencedEvent->event->tags->containEvery($this->tags)) {
            return;
        }
        $this->state = is_callable($this->handlers[$event::class]) ? $this->handlers[$event::class]($this->state, $event, $sequencedEvent) : $this->handlers[$event::class];
    }

    public function state(): mixed
    {
        return $this->state;
    }

    public function query(): Query
    {
        $eventTypes = array_map(static fn (string $domainEventClass) => substr($domainEventClass, strrpos($domainEventClass, '\\') + 1), array_keys($this->handlers));
        return Query::fromItems(
            QueryItem::create(eventTypes: $eventTypes, tags: $this->tags, onlyLastEvent: $this->onlyLastEvent),
        );
    }
}
