<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Closure;
use RuntimeException;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Types\AppendCondition;
use Wwwision\DCBEventStore\Types\Events;
use Wwwision\DCBEventStore\Types\ExpectedHighestSequenceNumber;
use Wwwision\DCBEventStore\Types\StreamQuery\StreamQuery;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\CourseRenamed;
use Wwwision\DCBExample\Event\DomainEvent;
use Wwwision\DCBExample\Event\DomainEvents;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Exception\ConstraintException;
use Wwwision\DCBExample\Projection\ClosureProjection;
use Wwwision\DCBExample\Projection\CompositeProjection;
use Wwwision\DCBExample\Projection\Projection;
use Wwwision\DCBExample\Projection\StreamCriteriaAware;
use Wwwision\DCBExample\Projection\TaggedProjection;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseIds;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;

use function sprintf;

/**
 * Main authority of this package, responsible to handle incoming {@see Command}s (@see self::handle()}
 */
final readonly class CommandHandler
{
    private EventSerializer $eventSerializer;

    public function __construct(
        private EventStore $eventStore,
    ) {
        $this->eventSerializer = new EventSerializer();
    }

    public function handle(Command $command): void
    {
        match ($command::class) {
            CreateCourse::class => $this->handleCreateCourse($command),
            RenameCourse::class => $this->handleRenameCourse($command),
            RegisterStudent::class => $this->handleRegisterStudent($command),
            SubscribeStudentToCourse::class => $this->handleSubscribeStudentToCourse($command),
            UnsubscribeStudentFromCourse::class => $this->handleUnsubscribeStudentFromCourse($command),
            UpdateCourseCapacity::class => $this->handleUpdateCourseCapacity($command),
            default => throw new RuntimeException(sprintf('Unsupported command %s', $command::class), 1684579212),
        };
    }

    private function handleCreateCourse(CreateCourse $command): void
    {
        $this->conditionalAppend(
            self::courseExists($command->courseId),
            function (bool $courseExists) use ($command) {
                if ($courseExists) {
                    throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $command->courseId->value), 1684593925);
                }
                return new CourseCreated($command->courseId, $command->initialCapacity, $command->courseTitle);
            }
        );
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $this->conditionalAppend(
            CompositeProjection::create([
                'courseExists' => self::courseExists($command->courseId),
                'courseTitle' => self::courseTitle($command->courseId),
            ]),
            /** @param object{courseExists: bool, courseTitle: CourseTitle} $state */
            function (object $state) use ($command) {
                if (!$state->courseExists) {
                    throw new ConstraintException(sprintf('Failed to rename course with id "%s" because a course with that id does not exist', $command->courseId->value), 1684509782);
                }
                if ($state->courseTitle->equals($command->newCourseTitle)) {
                    throw new ConstraintException(sprintf('Failed to rename course with id "%s" to "%s" because this is already the title of this course', $command->courseId->value, $command->newCourseTitle->value), 1684509837);
                }
                return new CourseRenamed($command->courseId, $command->newCourseTitle);
            }
        );
    }

    private function handleRegisterStudent(RegisterStudent $command): void
    {
        $this->conditionalAppend(
            CompositeProjection::create([
                'studentRegistered' => self::studentRegistered($command->studentId),
            ]),
            /** @param object{studentRegistered: bool} $state */
            function (object $state) use ($command) {
                if ($state->studentRegistered) {
                    throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $command->studentId->value), 1684579300);
                }
                return new StudentRegistered($command->studentId);
            }
        );
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $this->conditionalAppend(
            CompositeProjection::create([
                'studentRegistered' => self::studentRegistered($command->studentId),
                'courseExists' => self::courseExists($command->courseId),
                'courseCapacity' => self::courseCapacity($command->courseId),
                'numberOfCourseSubscriptions' => self::numberOfCourseSubscriptions($command->courseId),
                'studentSubscriptions' => self::studentSubscriptions($command->studentId),
            ]),
            /** @param object{studentRegistered: bool, courseExists: bool, courseCapacity: CourseCapacity, numberOfCourseSubscriptions: int, studentSubscriptions: CourseIds} $state */
            function (object $state) use ($command) {
                if (!$state->studentRegistered) {
                    throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1686914105);
                }
                if (!$state->courseExists) {
                    throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1685266122);
                }
                if ($state->courseCapacity->value === $state->numberOfCourseSubscriptions) {
                    throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because the course\'s capacity of %d is reached', $command->studentId->value, $command->courseId->value, $state->courseCapacity->value), 1684603201);
                }
                if ($state->studentSubscriptions->contains($command->courseId)) {
                    throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed to this course', $command->studentId->value, $command->courseId->value), 1684510963);
                }
                $maximumSubscriptionsPerStudent = 10;
                if ($state->studentSubscriptions->count() === $maximumSubscriptionsPerStudent) {
                    throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed the maximum of %d courses', $command->studentId->value, $command->courseId->value, $maximumSubscriptionsPerStudent), 1684605232);
                }
                return new StudentSubscribedToCourse($command->courseId, $command->studentId);
            }
        );
    }

    private function handleUnsubscribeStudentFromCourse(UnsubscribeStudentFromCourse $command): void
    {
        $this->conditionalAppend(
            CompositeProjection::create([
                'courseExists' => self::courseExists($command->courseId),
                'studentRegistered' => self::studentRegistered($command->studentId),
                'studentSubscriptions' => self::studentSubscriptions($command->studentId),
            ]),
            /** @param object{courseExists: bool, studentRegistered: bool, studentSubscriptions: CourseIds} $state */
            function (object $state) use ($command) {
                if (!$state->courseExists) {
                    throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579448);
                }
                if (!$state->studentRegistered) {
                    throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579463);
                }
                if (!$state->studentSubscriptions->contains($command->courseId)) {
                    throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because that student is not subscribed to this course', $command->studentId->value, $command->courseId->value), 1684579464);
                }
                return new StudentUnsubscribedFromCourse($command->studentId, $command->courseId);
            }
        );
    }

    private function handleUpdateCourseCapacity(UpdateCourseCapacity $command): void
    {
        $this->conditionalAppend(CompositeProjection::create([
            'courseExists' => self::courseExists($command->courseId),
            'courseCapacity' => self::courseCapacity($command->courseId),
            'numberOfCourseSubscriptions' => self::numberOfCourseSubscriptions($command->courseId),
        ]), function ($state) use ($command) {
            if (!$state->courseExists) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because a course with that id does not exist', $command->courseId->value, $command->newCapacity->value), 1684604283);
            }
            if ($state->courseCapacity->equals($command->newCapacity)) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because that is already the courses capacity', $command->courseId->value, $command->newCapacity->value), 1686819073);
            }
            if ($state->numberOfCourseSubscriptions > $command->newCapacity->value) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because it already has %d active subscriptions', $command->courseId->value, $command->newCapacity->value, $state->numberOfCourseSubscriptions), 1684604361);
            }
            return new CourseCapacityChanged($command->courseId, $command->newCapacity);
        });
    }

    // -----------------------------

    /**
     * @return Projection<bool>
     */
    private static function courseExists(CourseId $courseId): Projection
    {
        return TaggedProjection::create(
            $courseId->toTag(),
            ClosureProjection::create(
                initialState: false,
                onlyLastEvent: true,
            )
              ->when(CourseCreated::class, fn() => true)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseCapacity>
     */
    private static function courseCapacity(CourseId $courseId): Projection
    {
        return TaggedProjection::create(
            $courseId->toTag(),
            ClosureProjection::create(
                initialState: CourseCapacity::fromInteger(0),
            )
                ->when(CourseCreated::class, fn($_, CourseCreated $event) => $event->initialCapacity)
                ->when(CourseCapacityChanged::class, fn($_, CourseCapacityChanged $event) => $event->newCapacity)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseCapacity>
     */
    private static function numberOfCourseSubscriptions(CourseId $courseId): Projection
    {
        return TaggedProjection::create(
            $courseId->toTag(),
            ClosureProjection::create(
                initialState: 0,
            )
                ->when(StudentSubscribedToCourse::class, fn(int $state) => $state + 1)
                ->when(StudentUnsubscribedFromCourse::class, fn(int $state) => $state - 1)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseTitle>
     */
    private static function courseTitle(CourseId $courseId): Projection
    {
        return TaggedProjection::create(
            $courseId->toTag(),
            ClosureProjection::create(
                initialState: CourseTitle::fromString(''),
            )
            ->when(CourseCreated::class, static fn ($_, CourseCreated $event) => $event->courseTitle)
            ->when(CourseRenamed::class, static fn ($_, CourseRenamed $event) => $event->newCourseTitle)
        );
    }

    private static function studentRegistered(StudentId $studentId): Projection
    {
        return TaggedProjection::create(
            $studentId->toTag(),
            ClosureProjection::create(
                initialState: false,
                onlyLastEvent: true,
            )
                ->when(StudentRegistered::class, fn() => true)
        );
    }

    /**
     * @param StudentId $studentId
     * @return Projection<CourseIds>
     */
    private static function studentSubscriptions(StudentId $studentId): Projection
    {
        return TaggedProjection::create(
            $studentId->toTag(),
            ClosureProjection::create(
                initialState: CourseIds::none(),
            )
                ->when(StudentSubscribedToCourse::class, static fn (CourseIds $state, StudentSubscribedToCourse $event) => $state->with($event->courseId))
                ->when(StudentUnsubscribedFromCourse::class, static fn (CourseIds $state, StudentUnsubscribedFromCourse $event) => $state->without($event->courseId))
        );
    }

    /**
     * @template S
     * @param Projection<S> $projection
     * @param Closure(S): (DomainEvent|DomainEvents) $eventProducer
     * @return void
     */
    public function conditionalAppend(Projection $projection, Closure $eventProducer): void
    {
        $query = StreamQuery::wildcard();
        if ($projection instanceof StreamCriteriaAware) {
            $query = $query->withCriteria($projection->getCriteria());
        }
        $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::none();
        $state = $projection->initialState();
        foreach ($this->eventStore->read($query) as $eventEnvelope) {
            $domainEvent = $this->eventSerializer->convertEvent($eventEnvelope->event);
            $state = $projection->apply($state, $domainEvent, $eventEnvelope);
            $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::fromSequenceNumber($eventEnvelope->sequenceNumber);
        }
        $domainEvents = $eventProducer($state);
        if ($domainEvents instanceof DomainEvent) {
            $domainEvents = DomainEvents::create($domainEvents);
        }
        $events = Events::fromArray($domainEvents->map($this->eventSerializer->convertDomainEvent(...)));
        $this->eventStore->append($events, new AppendCondition($query, $expectedHighestSequenceNumber));
    }
}
