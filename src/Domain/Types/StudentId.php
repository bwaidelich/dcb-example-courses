<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Types;

use JsonSerializable;
use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBExample\Infrastructure\ProvidesTags;

/**
 * Globally unique identifier of a student (usually represented as a UUID v4)
 */
final readonly class StudentId implements ProvidesTags, JsonSerializable
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

    public function tags(): Tag
    {
        return Tag::fromString("student:$this->value");
    }
}
