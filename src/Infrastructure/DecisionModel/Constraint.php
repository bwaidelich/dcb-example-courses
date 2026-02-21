<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\DecisionModel;

use Closure;
use Wwwision\DCBExample\Infrastructure\Projection\Projection;

final readonly class Constraint
{
    /**
     * @template S
     * @param Projection<S> $projection
     * @param Closure(S): bool $transformer
     */
    private function __construct(
        public string $key,
        public Projection $projection,
        private Closure $transformer,
    ) {
    }

    /**
     * @template PS
     *
     * @param Projection<PS> $wrappedProjection
     * @param Closure(PS): bool $transformer
     */
    public static function create(string $key, Projection $wrappedProjection, Closure $transformer): self
    {
        return new self($key, $wrappedProjection, $transformer);
    }

    public function evaluate(mixed $state): bool
    {
        return ($this->transformer)($state);
    }
}
