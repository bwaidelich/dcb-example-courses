<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course\Dto;

use JsonSerializable;
use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBTools\Event\ProvidesTags;

/**
 * Globally unique identifier of a course (usually represented as a UUID v4)
 */
final readonly class CourseId implements ProvidesTags, JsonSerializable
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

    public function tags(): Tag
    {
        return Tag::fromString("course:$this->value");
    }
}
