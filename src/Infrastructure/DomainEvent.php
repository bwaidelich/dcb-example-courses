<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure;

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
