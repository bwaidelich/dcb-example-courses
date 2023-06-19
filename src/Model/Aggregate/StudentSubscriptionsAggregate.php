<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseIds;
use Wwwision\DCBExample\Model\StudentId;

/**
 * Event-sourced aggregate enforcing domain rules concerning the course subscriptions of a specific student
 */
final class StudentSubscriptionsAggregate implements Aggregate
{
    private CourseIds $subscribedCourseIds;

    public function __construct(
        private readonly StudentId $studentId,
    ) {
        $this->subscribedCourseIds = CourseIds::none();
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->subscribedCourseIds = match ($domainEvent::class) {
            StudentSubscribedToCourse::class => $this->subscribedCourseIds->with($domainEvent->courseId),
            StudentUnsubscribedFromCourse::class => $this->subscribedCourseIds->without($domainEvent->courseId),
            default => $this->subscribedCourseIds,
        };
    }

    public function subscribedToCourse(CourseId $courseId): bool
    {
        return $this->subscribedCourseIds->contains($courseId);
    }

    public function numberOfSubscriptions(): int
    {
        return $this->subscribedCourseIds->count();
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->studentId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('StudentSubscribedToCourse', 'StudentUnsubscribedFromCourse');
    }
}
