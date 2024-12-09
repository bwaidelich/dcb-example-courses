<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<DomainEvent>
 */
final class DomainEvents implements IteratorAggregate
{
    /**
     * @param array<DomainEvent> $domainEvents
     */
    private function __construct(private readonly array $domainEvents)
    {
    }

    public static function create(DomainEvent ...$domainEvents): self
    {
        return new self($domainEvents);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->domainEvents);
    }

    /**
     * @template S
     * @param Closure(DomainEvent): S $callback
     * @return array<S>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->domainEvents);
    }
}
