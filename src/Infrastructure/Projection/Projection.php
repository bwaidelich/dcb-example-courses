<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure\Projection;

use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * @template-covariant S
 */
interface Projection
{
    public function apply(DomainEvent $event, SequencedEvent $sequencedEvent): void;

    /**
     * @return S
     */
    public function state(): mixed;

    public function query(): Query;
}
