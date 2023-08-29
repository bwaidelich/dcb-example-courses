<?php /** @noinspection ALL */

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria\EventTypesAndTagsCriterion;
use Wwwision\DCBEventStore\Types\StreamQuery\StreamQuery;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Events\CourseCreated;
use Wwwision\DCBExample\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseStateValue;
use Wwwision\DCBLibrary\DomainEvent;
use Wwwision\DCBLibrary\Projection\Projection;
use Wwwision\DCBExample\Types\CourseState;
use Wwwision\DCBLibrary\StreamQueryAware;

/**
 * @implements Projection<CourseState>
 */
final class CourseStateProjection implements Projection, StreamQueryAware
{

    public function __construct(
        private readonly CourseId $courseId,
    ) {
    }

    public function initialState(): CourseState
    {
        return CourseState::initial();
    }

    /**
     * @param CourseState $state
     * @return CourseState
     */
    public function apply($state, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): CourseState
    {
        if (!$domainEvent->tags()->contain($this->courseId->toTag())) {
            return $state;
        }
        $handlerMethodName = 'when' . substr($domainEvent::class, strrpos($domainEvent::class, '\\') + 1);
        if (method_exists($this, $handlerMethodName)) {
            $state = $this->{$handlerMethodName}($state, $domainEvent);
        }
        return $state;
    }

    private function whenCourseCreated(CourseState $state, CourseCreated $event): CourseState
    {
        return $state->withValue(CourseStateValue::CREATED)->withCapacity($event->initialCapacity);
    }

    private function whenCourseCapacityChanged(CourseState $state, CourseCapacityChanged $event): CourseState
    {
        return $state->withCapacity($event->newCapacity);
    }

    private function whenStudentSubscribedToCourse(CourseState $state, StudentSubscribedToCourse $event): CourseState
    {
        $state = $state->withNumberOfSubscriptions($state->numberOfSubscriptions + 1);
        if ($state->numberOfSubscriptions === $state->capacity->value) {
            $state = $state->withValue(CourseStateValue::FULLY_BOOKED);
        }
        return $state;
    }

    private function whenStudentUnsubscribedFromCourse(CourseState $state, StudentUnsubscribedFromCourse $event): CourseState
    {
        $state = $state->withNumberOfSubscriptions($state->numberOfSubscriptions + 1);
        if ($state->numberOfSubscriptions < $state->capacity->value && $state->value === CourseStateValue::FULLY_BOOKED) {
            $state = $state->withValue(CourseStateValue::CREATED);
        }
        return $state;
    }

    public function adjustStreamQuery(StreamQuery $query): StreamQuery
    {
        return $query->withCriterion(new EventTypesAndTagsCriterion(
            EventTypes::fromArray(array_map(static fn (string $handlerMethodName) => substr($handlerMethodName, 4), array_filter(get_class_methods($this), static fn (string $methodName) => str_starts_with($methodName, 'when')))),
            Tags::create($this->courseId->toTag()),
        ));
    }
}
