<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\StringBased;
use function Wwwision\Types\instantiate;

#[Description('The title of a course')]
#[StringBased(maxLength: 100)]
final readonly class CourseTitle implements JsonSerializable
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        return instantiate(self::class, $value);
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
