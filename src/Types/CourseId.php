<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\DCBEventStore\Types\Tag;

/**
 * Globally unique identifier of a course (usually represented as a UUID v4)
 */
final readonly class CourseId implements JsonSerializable
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

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function toTag(): Tag
    {
        return Tag::create('course', $this->value);
    }
}
