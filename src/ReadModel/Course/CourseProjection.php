<?php /** @noinspection PhpUnusedPrivateMethodInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\ReadModel\Course;

use RuntimeException;
use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Types\EventEnvelope;
use Wwwision\DCBEventStore\Types\EventTypes;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\CourseStateProjection;
use Wwwision\DCBExample\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Events\CourseCreated;
use Wwwision\DCBExample\Events\CourseRenamed;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseState;
use Wwwision\DCBExample\Types\CourseStateValue;
use Wwwision\DCBLibrary\DomainEvent;
use Wwwision\DCBLibrary\EventTypesAware;
use Wwwision\DCBLibrary\Projection\PartitionedProjection;
use Wwwision\DCBLibrary\Projection\Projection;
use Wwwision\DCBLibrary\ProvidesReset;
use Wwwision\DCBLibrary\ProvidesSetup;
use Wwwision\DCBLibrary\TagsAware;

/**
 * @implements Projection<?Course>
 */
final readonly class CourseProjection implements PartitionedProjection, ProvidesReset, ProvidesSetup, EventTypesAware
{

    public function __construct(
        private CourseProjectionAdapter $adapter,
    ) {
    }

    public function partitionKey(DomainEvent $domainEvent): string
    {
        foreach ($domainEvent->tags() as $tag) {
            if ($tag->key === 'course') {
                return $tag->value;
            }
        }
        throw new RuntimeException(sprintf('Failed to partition projection %s for domain event of type %s', self::class, $domainEvent::class), 1693302957);
    }

    public function loadState(string $partitionKey): ?Course
    {
        return $this->adapter->courseById(CourseId::fromString($partitionKey));
    }

    public function saveState($state): void
    {
        Assert::isInstanceOf($state, Course::class);
        $this->adapter->saveCourse($state);
    }

    public function initialState(): null
    {
        return null;
    }

    /**
     * @param Course|null $course
     * @param DomainEvent $domainEvent
     * @param EventEnvelope $eventEnvelope
     * @return ?Course
     */
    public function apply($course, DomainEvent $domainEvent, EventEnvelope $eventEnvelope): ?Course
    {
        if ($course instanceof Course) {
            $courseStateProjection = new CourseStateProjection($course->id);
            $course = $course->withState($courseStateProjection->apply($course->state, $domainEvent, $eventEnvelope));
        }
        $handlerMethodName = 'when' . substr($domainEvent::class, strrpos($domainEvent::class, '\\') + 1);
        if (method_exists($this, $handlerMethodName)) {
            $course = $this->{$handlerMethodName}($course, $domainEvent);
        }
        return $course;
    }

    private function whenCourseCreated($_, CourseCreated $domainEvent): Course
    {
        return new Course($domainEvent->courseId, $domainEvent->courseTitle, CourseState::initial());
    }

    private function whenCourseCapacityChanged(Course $course, CourseCapacityChanged $domainEvent): Course
    {
        return $course->withState($course->state->withCapacity($domainEvent->newCapacity));
    }


    public function reset(): void
    {
        $this->adapter->reset();
    }

    public function setup(): void
    {
        $this->adapter->setup();
    }

    public function eventTypes(): EventTypes
    {
        return EventTypes::fromArray(array_map(static fn (string $handlerMethodName) => substr($handlerMethodName, 4), array_filter(get_class_methods($this), static fn (string $methodName) => str_starts_with($methodName, 'when'))));
    }
}