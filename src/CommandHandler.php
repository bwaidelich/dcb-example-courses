<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Closure;
use RuntimeException;
use stdClass;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Types\AppendCondition;
use Wwwision\DCBEventStore\Types\Events;
use Wwwision\DCBEventStore\Types\ExpectedHighestSequenceNumber;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria;
use Wwwision\DCBEventStore\Types\StreamQuery\Criteria\EventTypesAndTagsCriterion;
use Wwwision\DCBEventStore\Types\StreamQuery\StreamQuery;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Commands\Command;
use Wwwision\DCBExample\Commands\CreateCourse;
use Wwwision\DCBExample\Commands\RegisterStudent;
use Wwwision\DCBExample\Commands\RenameCourse;
use Wwwision\DCBExample\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Commands\UpdateCourseCapacity;
use Wwwision\DCBExample\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Events\CourseCreated;
use Wwwision\DCBExample\Events\CourseRenamed;
use Wwwision\DCBExample\Events\StudentRegistered;
use Wwwision\DCBExample\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Exception\ConstraintException;
use Wwwision\DCBExample\Projections\GenericProjection;
use Wwwision\DCBExample\Projections\Projection;
use Wwwision\DCBExample\Projections\ProjectionLogic;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseIds;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;
use function array_map;
use function is_array;
use function sprintf;

/**
 * Main authority of this package, responsible to handle incoming {@see Command}s (@see self::handle()}
 */
final readonly class CommandHandler
{
    private EventNormalizer $eventNormalizer;

    public function __construct(
        private EventStore $eventStore,
    ) {
        $this->eventNormalizer = new EventNormalizer();
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
        $this->transactional([
            'courseExists' => self::courseExists($command->courseId),
        ], function ($state) use ($command) {
            if ($state->courseExists) {
                throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $command->courseId->value), 1684593925);
            }
            return new CourseCreated($command->courseId, $command->initialCapacity, $command->courseTitle);
        });
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $this->transactional([
            'courseExists' => self::courseExists($command->courseId),
            'courseTitle' => self::courseTitle($command->courseId),
        ], function ($state) use ($command) {
            if (!$state->courseExists) {
                throw new ConstraintException(sprintf('Failed to rename course with id "%s" because a course with that id does not exist', $command->courseId->value), 1684509782);
            }
            if ($state->courseTitle !== null && $state->courseTitle->equals($command->newCourseTitle)) {
                throw new ConstraintException(sprintf('Failed to rename course with id "%s" to "%s" because this is already the title of this course', $command->courseId->value, $command->newCourseTitle->value), 1684509837);
            }
            return new CourseRenamed($command->courseId, $command->newCourseTitle);
        });
    }

    private function handleRegisterStudent(RegisterStudent $command): void
    {
        $this->transactional([
            'studentRegistered' => self::studentRegistered($command->studentId),
        ], function ($state) use ($command) {
            if ($state->studentRegistered) {
                throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $command->studentId->value), 1684579300);
            }
            return new StudentRegistered($command->studentId);
        });
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $this->transactional([
            'studentRegistered' => self::studentRegistered($command->studentId),
            'courseExists' => self::courseExists($command->courseId),
            'courseCapacity' => self::courseCapacity($command->courseId),
            'numberOfCourseSubscriptions' => self::numberOfCourseSubscriptions($command->courseId),
            'studentSubscriptions' => self::studentSubscriptions($command->studentId),
        ], function ($state) use ($command) {
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
        });
    }

    private function handleUnsubscribeStudentFromCourse(UnsubscribeStudentFromCourse $command): void
    {
        $this->transactional([
            'courseExists' => self::courseExists($command->courseId),
            'studentRegistered' => self::studentRegistered($command->studentId),
            'studentSubscriptions' => self::studentSubscriptions($command->studentId),
        ], function ($state) use ($command) {
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
        });
    }

    private function handleUpdateCourseCapacity(UpdateCourseCapacity $command): void
    {
        $this->transactional([
            'courseExists' => self::courseExists($command->courseId),
            'courseCapacity' => self::courseCapacity($command->courseId),
            'numberOfCourseSubscriptions' => self::numberOfCourseSubscriptions($command->courseId),
        ], function ($state) use ($command) {
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
        return new GenericProjection(
            Tags::create($courseId->toTag()),
            (new ProjectionLogic(false))
                ->when(CourseCreated::class, static fn() => true)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseCapacity>
     */
    private static function courseCapacity(CourseId $courseId): Projection
    {
        return new GenericProjection(
            Tags::create($courseId->toTag()),
            (new ProjectionLogic(CourseCapacity::fromInteger(0)))
                ->when(CourseCreated::class, static fn($_, CourseCreated $event) => $event->initialCapacity)
                ->when(CourseCapacityChanged::class, static fn($_, CourseCapacityChanged $event) => $event->newCapacity)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseCapacity>
     */
    private static function numberOfCourseSubscriptions(CourseId $courseId): Projection
    {
        return new GenericProjection(
            Tags::create($courseId->toTag()),
            (new ProjectionLogic(0))
                ->when(StudentSubscribedToCourse::class, static fn(int $state) => $state + 1)
                ->when(StudentUnsubscribedFromCourse::class, static fn(int $state) => $state - 1)
        );
    }

    /**
     * @param CourseId $courseId
     * @return Projection<CourseTitle>
     */
    private static function courseTitle(CourseId $courseId): Projection
    {
        /** @var ProjectionLogic<CourseTitle> $logic */
        $logic = (new ProjectionLogic(CourseTitle::fromString('')))
            ->when(CourseCreated::class, static fn ($_, CourseCreated $event) => $event->courseTitle)
            ->when(CourseRenamed::class, static fn ($_, CourseRenamed $event) => $event->newCourseTitle);
        return new GenericProjection(Tags::create($courseId->toTag()), $logic);
    }

    private static function studentRegistered(StudentId $studentId): Projection
    {
        return new GenericProjection(
            Tags::create($studentId->toTag()),
            (new ProjectionLogic(false))
                ->when(StudentRegistered::class, static fn() => true)
        );
    }

    /**
     * @param StudentId $studentId
     * @return Projection<CourseIds>
     */
    private static function studentSubscriptions(StudentId $studentId): Projection
    {
        /** @var ProjectionLogic<bool> $logic */
        $logic = (new ProjectionLogic(CourseIds::none()))
            ->when(StudentSubscribedToCourse::class, static fn (CourseIds $state, StudentSubscribedToCourse $event) => $state->with($event->courseId))
            ->when(StudentUnsubscribedFromCourse::class, static fn (CourseIds $state, StudentUnsubscribedFromCourse $event) => $state->without($event->courseId));

        return new GenericProjection(Tags::create($studentId->toTag()), $logic);
    }

    /**
     * @param array<string, Projection> $projections
     */
    private function transactional(array $projections, Closure $closure): void
    {
        $criteria = [];
        foreach ($projections as $projection) {
            $criteria[] = new EventTypesAndTagsCriterion($projection->eventTypes(), $projection->tags());
        }
        $query = StreamQuery::create(Criteria::fromArray($criteria));
        $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::none();
        foreach ($this->eventStore->read($query) as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->convertEvent($eventEnvelope);
            foreach ($projections as $projection) {
                if (!$projection->eventTypes()->contain($eventEnvelope->event->type)) {
                    continue;
                }
                if (!$domainEvent->tags()->containEvery($projection->tags())) {
                    continue;
                }
                $projection->apply($domainEvent);
            }
            $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::fromSequenceNumber($eventEnvelope->sequenceNumber);
        }
        $state = new stdClass();
        foreach ($projections as $key => $projection) {
            $state->$key = $projection->getState();
        }
        $domainEvents = $closure($state);
        $events = Events::fromArray(array_map($this->eventNormalizer->convertDomainEvent(...), is_array($domainEvents) ? $domainEvents : [$domainEvents]));
        $this->eventStore->append($events, new AppendCondition($query, $expectedHighestSequenceNumber));

    }
}
