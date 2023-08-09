<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Wwwision\DCBEventStore\Types\Tags;

/**
 * Contract for Domain Events classes
 */
interface DomainEvent
{
    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self;

    public function tags(): Tags;
}
