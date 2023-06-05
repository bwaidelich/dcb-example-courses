<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model;

use JsonSerializable;

/**
 * The title of a course
 */
final readonly class CourseTitle implements JsonSerializable
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
}
