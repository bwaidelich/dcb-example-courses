<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Aggregate;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainId;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\CourseRenamed;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;

/**
 * Event-sourced aggregate enforcing domain rules concerning the title of a single course
 */
final class CourseTitleAggregate implements Aggregate
{
    public ?CourseTitle $courseTitle = null;

    public function __construct(private readonly CourseId $courseId)
    {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->courseTitle = match ($domainEvent::class) {
            CourseCreated::class => $domainEvent->courseTitle,
            CourseRenamed::class => $domainEvent->newCourseTitle,
            default => $this->courseTitle,
        };
    }

    public function domainIds(): DomainId
    {
        return $this->courseId;
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated', 'CourseRenamed');
    }
}
