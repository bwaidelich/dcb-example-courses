<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\DCBEventStore\Types\Tag;

/**
 * Globally unique identifier of a student (usually represented as a UUID v4)
 */
final readonly class StudentId implements JsonSerializable
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

    public function toTag(): Tag
    {
        return Tag::fromString("student:$this->value");
    }
}
