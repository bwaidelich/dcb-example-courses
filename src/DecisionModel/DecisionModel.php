<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\DecisionModel;

use Wwwision\DCBEventStore\Types\AppendCondition;

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
