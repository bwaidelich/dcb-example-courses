<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\Projection;

use stdClass;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * @template S of object
 *
 * @implements Projection<S>
 */
final readonly class CompositeProjection implements Projection
{
    /**
     * @param non-empty-array<string|int, Projection<mixed>> $projectors
     */
    private function __construct(
        private array $projectors,
        private string $stateClassName,
    ) {
    }

    /**
     * @template PS of object
     *
     * @param non-empty-array<string|int, Projection<mixed>> $projectors
     * @param class-string<PS> $stateClassName
     *
     * @return self<PS>
     */
    public static function create(array $projectors, string $stateClassName): self
    {
        /** @var self<PS> */
        return new self($projectors, $stateClassName);
    }

    public function apply(DomainEvent $event, SequencedEvent $sequencedEvent): void
    {
        foreach ($this->projectors as $projector) {
            $projector->apply($event, $sequencedEvent);
        }
    }

    public function state(): object
    {
        $state = [];
        foreach ($this->projectors as $projectorKey => $projector) {
            $state[$projectorKey] = $projector->state();
        }
        if ($this->stateClassName === stdClass::class) {
            /** @var S */ // @phpstan-ignore varTag.nativeType
            return (object)$state;
        }
        /** @var S */
        return new ($this->stateClassName)(...$state);
    }

    public function query(): Query
    {
        /** @var Query|null $query */
        $query = null;
        foreach ($this->projectors as $projector) {
            $query = $query === null ? $projector->query() : $query->merge($projector->query());
        }
        return $query;
    }
}
