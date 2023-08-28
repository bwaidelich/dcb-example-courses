<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

/**
 * Contract for Domain Events classes
 */
interface DomainEvent extends \Wwwision\DCBLibrary\DomainEvent
{
    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self;
}
