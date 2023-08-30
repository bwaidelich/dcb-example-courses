<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\IntegerBased;
use function Wwwision\Types\instantiate;

#[Description('Total capacity (available seats) of a course')]
#[IntegerBased(minimum: 0)]
final readonly class CourseCapacity implements JsonSerializable
{
    private function __construct(public int $value)
    {
    }

    public static function fromInteger(int $value): self
    {
        return instantiate(self::class, $value);
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
