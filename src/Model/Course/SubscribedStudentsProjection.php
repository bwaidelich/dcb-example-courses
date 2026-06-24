<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course;

use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\Query\QueryItem;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Student\Dto\StudentIds;
use Wwwision\DCBTools\Event\DomainEvent;
use Wwwision\DCBTools\Projection\Projection;
use Wwwision\DCBTools\Serialization\EventSerializer;

/**
 * Projects the set of students subscribed to ANY of the given courses.
 *
 * Unlike a single {@see \Wwwision\DCBTools\Projection\AtomicProjection} over the merged course tags – which would match
 * events containing EVERY course tag (AND) and therefore resolve to no students for two or more courses – this emits one
 * query item per course (OR) and folds by checking course membership, so the result and its consistency boundary are
 * both correct and tag-scoped.
 *
 * @implements Projection<StudentIds>
 */
final class SubscribedStudentsProjection implements Projection
{
    private StudentIds $state;

    public function __construct(
        private readonly CourseIds $courseIds,
    ) {
        $this->state = StudentIds::none();
    }

    public function apply(DomainEvent $event, SequencedEvent $envelope): void
    {
        if ($event instanceof StudentSubscribedToCourse && $this->courseIds->contains($event->courseId)) {
            $this->state = $this->state->with($event->studentId);
        } elseif ($event instanceof StudentUnsubscribedFromCourse && $this->courseIds->contains($event->courseId)) {
            $this->state = $this->state->without($event->studentId);
        }
    }

    public function state(): StudentIds
    {
        return $this->state;
    }

    public function query(EventSerializer $eventSerializer): Query
    {
        $eventTypes = [
            $eventSerializer->resolveEventType(StudentSubscribedToCourse::class),
            $eventSerializer->resolveEventType(StudentUnsubscribedFromCourse::class),
        ];
        $items = [];
        foreach ($this->courseIds as $courseId) {
            $items[] = QueryItem::create(eventTypes: $eventTypes, tags: [$courseId->tags()]);
        }
        if ($items === []) {
            // No courses to consider: a bounded query that matches no event (rather than scanning all subscriptions).
            $items[] = QueryItem::create(eventTypes: $eventTypes, tags: [Tag::fromString('none:none')]);
        }
        return Query::fromItems(...$items);
    }
}
