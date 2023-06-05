<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model;

use JsonSerializable;
use Wwwision\DCBEventStore\Model\DomainId;

/**
 * Globally unique identifier of a student (usually represented as a UUID v4)
 */
final readonly class StudentId implements DomainId, JsonSerializable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }


    public function key(): string
    {
        return 'student';
    }

    public function value(): string
    {
        return $this->value;
    }
}
