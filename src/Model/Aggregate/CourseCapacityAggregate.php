<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainId;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\Aggregate\State\CourseSubscriptionsState;
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;

/**
 * Event-sourced aggregate enforcing domain rules concerning the total capacity of a single course
 */
final class CourseCapacityAggregate implements Aggregate
{
    private CourseSubscriptionsState $state;

    public function __construct(
        private readonly CourseId $courseId,
    ) {
        $this->state = new CourseSubscriptionsState(CourseCapacity::fromInteger(0), 0);
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->state = match ($domainEvent::class) {
            CourseCreated::class => $this->state->withCourseCapacity($domainEvent->initialCapacity),
            CourseCapacityChanged::class => $this->state->withCourseCapacity($domainEvent->newCapacity),
            StudentSubscribedToCourse::class => $this->state->withAddedSubscription(),
            StudentUnsubscribedFromCourse::class => $this->state->withRemovedSubscription(),
            default => $this->state,
        };
    }

    public function courseCapacityReached(): bool
    {
        return $this->state->numberOfSubscriptions >= $this->state->courseCapacity->value;
    }

    public function courseCapacity(): CourseCapacity
    {
        return $this->state->courseCapacity;
    }

    public function numberOfSubscriptions(): int
    {
        return $this->state->numberOfSubscriptions;
    }

    public function domainIds(): DomainId
    {
        return $this->courseId;
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated', 'CourseCapacityChanged', 'StudentSubscribedToCourse', 'StudentUnsubscribedFromCourse');
    }
}
