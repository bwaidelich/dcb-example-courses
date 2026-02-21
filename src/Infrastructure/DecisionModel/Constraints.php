<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\DecisionModel;

use Closure;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<Constraint>
 */
final readonly class Constraints implements IteratorAggregate
{
    /**
     * @param list<Constraint> $constraints
     */
    private function __construct(
        private array $constraints,
    ) {
    }

    public static function create(Constraint ...$constraints): self
    {
        return new self(array_values($constraints));
    }

    public function getIterator(): Traversable
    {
        yield from $this->constraints;
    }

    /**
     * @template T
     * @param Closure(Constraint): T $callback
     * @return list<T>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->constraints);
    }
}
