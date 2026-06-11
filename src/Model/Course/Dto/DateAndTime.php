<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course\Dto;

use DateTimeImmutable;
use JsonSerializable;

final readonly class DateAndTime implements JsonSerializable
{
    private const string FORMAT = 'Y-m-d H:i:s';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromPhpDateTime(DateTimeImmutable $phpDateTime): self
    {
        return new self($phpDateTime->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT));
    }

    public function toPhpDateTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(self::FORMAT, $this->value, new \DateTimeZone('UTC'));
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
