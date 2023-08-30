<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;
use Wwwision\Types\Attributes\Description;

#[Description('Finite state a course can be in')]
enum CourseStateValue implements JsonSerializable
{
    case NON_EXISTING;
    case CREATED;
    case FULLY_BOOKED;

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
