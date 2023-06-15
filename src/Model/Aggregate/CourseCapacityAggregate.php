<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Aggregate\Aggregate;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBEventStore\Aggregate\AggregateTrait;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\Aggregate\State\CourseSubscriptionsState;
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;

use function sprintf;

/**
 * Event-sourced aggregate enforcing domain rules concerning the total capacity of a single course
 */
final class CourseCapacityAggregate implements Aggregate
{
    use AggregateTrait;

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

    public function changeCourseCapacity(CourseCapacity $newCapacity): void
    {
        if ($newCapacity->equals($this->state->courseCapacity)) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because that is already the courses capacity', $this->courseId->value, $newCapacity->value), 1686819073);
        }
        if ($this->state->numberOfSubscriptions > $newCapacity->value) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because it already has %d active subscriptions', $this->courseId->value, $newCapacity->value, $this->state->numberOfSubscriptions), 1684604361);
        }
        $this->record(new CourseCapacityChanged($this->courseId, $newCapacity));
    }

    public function isCourseCapacityReached(): bool
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

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated', 'CourseCapacityChanged', 'StudentSubscribedToCourse', 'StudentUnsubscribedFromCourse');
    }
}
