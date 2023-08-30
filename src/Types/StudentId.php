<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\DCBEventStore\Types\Tag;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\StringBased;
use function Wwwision\Types\instantiate;

#[Description('Globally unique identifier of a student (usually represented as a UUID v4)')]
#[StringBased]
final readonly class StudentId implements JsonSerializable
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

    public function toTag(): Tag
    {
        return Tag::create('student', $this->value);
    }
}
