<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain;

use Closure;
use stdClass;
use Wwwision\DCBEventStore\AppendCondition\AppendCondition;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBExample\Domain\DecisionModel\CourseDecisionModels as Course;
use Wwwision\DCBExample\Domain\DecisionModel\StudentDecisionModels as Student;
use Wwwision\DCBExample\Domain\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Domain\Event\CourseDefined;
use Wwwision\DCBExample\Domain\Event\CourseRenamed;
use Wwwision\DCBExample\Domain\Event\StudentRegistered;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Types\CourseCapacity;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseTitle;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraint;
use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraints;
use Wwwision\DCBExample\Infrastructure\DomainEvent;
use Wwwision\DCBExample\Infrastructure\EventSerializer;
use Wwwision\DCBExample\Infrastructure\Exception\ConstraintException;
use Wwwision\DCBExample\Infrastructure\Projection\CompositeProjection;

use function sprintf;
use function Wwwision\DCBExample\not;

/**
 * Main authority of this package, responsible to handle incoming commands
 */
final readonly class App
{
    private EventSerializer $eventSerializer;

    public function __construct(
        private EventStore $eventStore,
    ) {
        $this->eventSerializer = new EventSerializer('\\Wwwision\\DCBExample\\Domain\\Event');
    }

    public function defineCourse(CourseId $courseId, CourseTitle $courseTitle, CourseCapacity $initialCapacity): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                not(Course::exists($courseId))
            ),
            static fn () => new CourseDefined($courseId, $initialCapacity, $courseTitle),
        );
    }

    public function renameCourse(CourseId $courseId, CourseTitle $newTitle): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                Course::exists($courseId),
                not(Course::titleEquals($courseId, $newTitle)),
            ),
            static fn () => new CourseRenamed($courseId, $newTitle),
        );
    }

    public function registerStudent(StudentId $studentId): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                not(Student::isRegistered($studentId))
            ),
            fn () => new StudentRegistered($studentId),
        );
    }

    public function subscribeStudentToCourse(StudentId $studentId, CourseId $courseId): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                Course::exists($courseId),
                Student::isRegistered($studentId),
                Course::hasFreeSeats($courseId),
                not(Student::isSubscribedToCourse($studentId, $courseId)),
                Student::numberOfSubscriptionsIsBelowLimit($studentId),
            ),
            fn () => new StudentSubscribedToCourse($courseId, $studentId),
        );
    }

    public function unsubscribeStudentFromCourse(StudentId $studentId, CourseId $courseId): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                Course::exists($courseId),
                Student::isRegistered($studentId),
                Student::isSubscribedToCourse($studentId, $courseId),
            ),
            static fn () => new StudentUnsubscribedFromCourse($studentId, $courseId),
        );
    }

    public function changeCourseCapacity(CourseId $courseId, CourseCapacity $newCapacity): void
    {
        $this->appendEventConditionally(
            Constraints::create(
                Course::exists($courseId),
                not(Course::capacityEquals($courseId, $newCapacity)),
                Course::numberOfSubscriptionsIsBelowCapacity($courseId, $newCapacity->value),
            ),
            static fn () => new CourseCapacityChanged($courseId, $newCapacity),
        );
    }

    // ------------------------------------

    /**
     * @param Closure(): DomainEvent $eventProducer
     */
    private function appendEventConditionally(Constraints $constraints, Closure $eventProducer): void
    {
        $projections = $constraints->map(static fn (Constraint $c) => $c->projection);
        assert($projections !== []);
        $compositeProjection = CompositeProjection::create($projections, stdClass::class);
        $query = $compositeProjection->query();
        $highestMatchingPosition = null;
        foreach ($this->eventStore->read($query) as $sequencedEvent) {
            $domainEvent = $this->eventSerializer->convertEvent($sequencedEvent->event);
            $compositeProjection->apply($domainEvent, $sequencedEvent);
            $highestMatchingPosition = $sequencedEvent->position;
        }
        $constraintStates = (array)$compositeProjection->state();

        $index = 0;
        foreach ($constraints as $constraint) {
            $constraintResult = $constraint->evaluate($constraintStates[$index] ?? null);
            if ($constraintResult !== true) {
                throw new ConstraintException(sprintf('Constraint "%s" failed', $constraint->key));
            }
            $index++;
        }

        $appendCondition = AppendCondition::create(failIfEventsMatch: $query, after: $highestMatchingPosition);
        $domainEvent = ($eventProducer)();
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $appendCondition);
    }
}
