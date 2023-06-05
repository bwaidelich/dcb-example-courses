<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Event\Normalizer;

/**
 * Contract for classes (usually Domain Events) with a static fromArray() constructor
 */
interface FromArraySupport
{
    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self;
}
