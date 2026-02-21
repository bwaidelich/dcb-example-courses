<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\DecisionModel;

use Wwwision\DCBEventStore\AppendCondition\AppendCondition;

/**
 * @template S of object
 */
final readonly class DecisionModel
{
    /**
     * @param S $state
     */
    public function __construct(
        public mixed $state,
        public AppendCondition $appendCondition,
    ) {
    }
}
