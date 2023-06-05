<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate\State;

use Wwwision\DCBExample\Model\Aggregate\CourseCapacityAggregate;
use Wwwision\DCBExample\Model\CourseCapacity;

/**
 * @internal State of the {@see CourseCapacityAggregate}
 */
final readonly class CourseSubscriptionsState
{
    public function __construct(
        public readonly CourseCapacity $courseCapacity,
        public readonly int $numberOfSubscriptions,
    ) {
    }

    public function withCourseCapacity(CourseCapacity $capacity): self
    {
        return new self($capacity, $this->numberOfSubscriptions);
    }

    public function withAddedSubscription(): self
    {
        return new self($this->courseCapacity, $this->numberOfSubscriptions + 1);
    }

    public function withRemovedSubscription(): self
    {
        return new self($this->courseCapacity, $this->numberOfSubscriptions - 1);
    }
}
