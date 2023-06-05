<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Aggregate\Aggregate;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBEventStore\Aggregate\AggregateTrait;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\CourseRenamed;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;

use function sprintf;

/**
 * Event-sourced aggregate enforcing domain rules concerning the title of a single course
 */
final class CourseTitleAggregate implements Aggregate
{
    use AggregateTrait;

    private ?CourseTitle $courseTitle = null;

    public function __construct(
        private readonly CourseId $courseId,
    ) {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->courseTitle = match ($domainEvent::class) {
            CourseCreated::class => $domainEvent->courseTitle,
            CourseRenamed::class => $domainEvent->newCourseTitle,
            default => $this->courseTitle,
        };
    }

    public function renameCourse(CourseTitle $newCourseTitle): void
    {
        if ($this->courseTitle !== null && $this->courseTitle->equals($newCourseTitle)) {
            throw new ConstraintException(sprintf('Failed to rename course with id "%s" to "%s" because this is already the title of this course', $this->courseId->value, $newCourseTitle->value), 1684509837);
        }
        $this->record(new CourseRenamed($this->courseId, $newCourseTitle));
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated', 'CourseRenamed');
    }
}
