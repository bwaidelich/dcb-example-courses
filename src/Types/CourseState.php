<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use JsonSerializable;

final readonly class CourseState
{
    private function __construct(
        public CourseStateValue $value,
        public CourseCapacity $capacity,
        public int $numberOfSubscriptions,
    ) {
    }

    public static function initial(): self
    {
        return new self(
            CourseStateValue::NON_EXISTING,
            CourseCapacity::fromInteger(0),
            0
        );
    }

    public function withValue(CourseStateValue $value): self
    {
        if ($value === $this->value) {
            return $this;
        }
        return new self(
            $value,
            $this->capacity,
            $this->numberOfSubscriptions,
        );
    }

    public function withCapacity(CourseCapacity $newCapacity): self
    {
        if ($newCapacity->equals($this->capacity)) {
            return $this;
        }
        return new self(
            $this->value,
            $newCapacity,
            $this->numberOfSubscriptions,
        );
    }

    public function withNumberOfSubscriptions(int $newNumberOfSubscriptions): self
    {
        if ($newNumberOfSubscriptions === $this->numberOfSubscriptions) {
            return $this;
        }
        return new self(
            $this->value,
            $this->capacity,
            $newNumberOfSubscriptions,
        );
    }
}
