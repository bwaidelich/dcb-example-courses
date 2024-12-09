<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

/**
 * Contract for Domain Events classes
 */
interface DomainEvent
{
    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self;
}
