<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Aggregate\Aggregate;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBEventStore\Aggregate\AggregateTrait;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseIds;
use Wwwision\DCBExample\Model\StudentId;

use function sprintf;

/**
 * Event-sourced aggregate enforcing domain rules concerning the course subscriptions of a specific student
 */
final class StudentSubscriptionsAggregate implements Aggregate
{
    use AggregateTrait;

    private const MAX_SUBSCRIPTIONS_PER_STUDENT = 10;

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

    public function subscribeToCourse(CourseId $courseId): void
    {
        if ($this->subscribedCourseIds->contains($courseId)) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed to this course', $this->studentId->value, $courseId->value), 1684510963);
        }
        if ($this->subscribedCourseIds->count() >= self::MAX_SUBSCRIPTIONS_PER_STUDENT) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed the maximum of %d courses', $this->studentId->value, $courseId->value, self::MAX_SUBSCRIPTIONS_PER_STUDENT), 1684605232);
        }
        $this->record(new StudentSubscribedToCourse($courseId, $this->studentId));
    }

    public function unsubscribeFromCourse(CourseId $courseId): void
    {
        if (!$this->subscribedCourseIds->contains($courseId)) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because that student is not subscribed to this course', $this->studentId->value, $courseId->value), 1684579464);
        }
        $this->record(new StudentUnsubscribedFromCourse($this->studentId, $courseId));
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->studentId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('StudentRegistered');
    }
}
