<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Webmozart\Assert\Assert;

/**
 * Total capacity (available seats) of a course
 */
final readonly class CourseCapacity implements JsonSerializable
{
    private function __construct(public int $value)
    {
        Assert::natural($this->value, 'Capacity must not be a negative value, given: %d');
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
