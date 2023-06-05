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
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;

use function sprintf;

/**
 * Event-sourced aggregate enforcing domain rules concerning the existence of a specific course
 */
final class CourseExistenceAggregate implements Aggregate
{
    use AggregateTrait;

    private bool $courseExists = false;


    public function __construct(
        private readonly CourseId $courseId,
    ) {
    }

    public function apply(DomainEvent $domainEvent): void
    {
        $this->courseExists = match ($domainEvent::class) {
            CourseCreated::class => true,
            default => $this->courseExists,
        };
    }

    public function courseExists(): bool
    {
        return $this->courseExists;
    }

    public function createCourse(CourseCapacity $initialCapacity, CourseTitle $courseTitle): void
    {
        if ($this->courseExists()) {
            throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $this->courseId->value), 1684593925);
        }
        $this->record(new CourseCreated($this->courseId, $initialCapacity, $courseTitle));
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromStrings('CourseCreated');
    }
}
