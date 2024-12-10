<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use RuntimeException;
use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Types\AppendCondition;
use Wwwision\DCBEventStore\Types\ExpectedHighestSequenceNumber;
use Wwwision\DCBEventStore\Types\StreamQuery\StreamQuery;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\DecisionModel\DecisionModel;
use Wwwision\DCBExample\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\CourseRenamed;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Exception\ConstraintException;
use Wwwision\DCBExample\Projection\ClosureProjection;
use Wwwision\DCBExample\Projection\CompositeProjection;
use Wwwision\DCBExample\Projection\Projection;
use Wwwision\DCBExample\Projection\TaggedProjection;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseIds;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;

use function PHPStan\dumpType;
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
        $decisionModel = $this->buildDecisionModel(
            courseExists: self::courseExists($command->courseId),
        );
        if ($decisionModel->state->courseExists) {
            throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $command->courseId->value), 1684593925);
        }
        $domainEvent = new CourseCreated($command->courseId, $command->initialCapacity, $command->courseTitle);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $decisionModel = $this->buildDecisionModel(
            courseExists: self::courseExists($command->courseId),
            courseTitle: self::courseTitle($command->courseId),
        );
        if (!$decisionModel->state->courseExists) {
            throw new ConstraintException(sprintf('Failed to rename course with id "%s" because a course with that id does not exist', $command->courseId->value), 1684509782);
        }
        if ($decisionModel->state->courseTitle->equals($command->newCourseTitle)) {
            throw new ConstraintException(sprintf('Failed to rename course with id "%s" to "%s" because this is already the title of this course', $command->courseId->value, $command->newCourseTitle->value), 1684509837);
        }
        $domainEvent = new CourseRenamed($command->courseId, $command->newCourseTitle);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
    }

    private function handleRegisterStudent(RegisterStudent $command): void
    {
        $decisionModel = $this->buildDecisionModel(
            studentRegistered: self::studentRegistered($command->studentId),
        );
        if ($decisionModel->state->studentRegistered) {
            throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $command->studentId->value), 1684579300);
        }
        $domainEvent = new StudentRegistered($command->studentId);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $decisionModel = $this->buildDecisionModel(
            courseExists: self::courseExists($command->courseId),
            studentRegistered: self::studentRegistered($command->studentId),
            courseCapacity: self::courseCapacity($command->courseId),
            numberOfCourseSubscriptions: self::numberOfCourseSubscriptions($command->courseId),
            studentSubscriptions: self::studentSubscriptions($command->studentId),
        );
        if (!$decisionModel->state->courseExists) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1685266122);
        }
        if (!$decisionModel->state->studentRegistered) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1686914105);
        }
        if ($decisionModel->state->courseCapacity->value === $decisionModel->state->numberOfCourseSubscriptions) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because the course\'s capacity of %d is reached', $command->studentId->value, $command->courseId->value, $decisionModel->state->courseCapacity->value), 1684603201);
        }
        if ($decisionModel->state->studentSubscriptions->contains($command->courseId)) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed to this course', $command->studentId->value, $command->courseId->value), 1684510963);
        }
        $maximumSubscriptionsPerStudent = 10;
        if ($decisionModel->state->studentSubscriptions->count() === $maximumSubscriptionsPerStudent) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed the maximum of %d courses', $command->studentId->value, $command->courseId->value, $maximumSubscriptionsPerStudent), 1684605232);
        }
        $domainEvent = new StudentSubscribedToCourse($command->courseId, $command->studentId);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
    }

    private function handleUnsubscribeStudentFromCourse(UnsubscribeStudentFromCourse $command): void
    {
        $decisionModel = $this->buildDecisionModel(
            courseExists: self::courseExists($command->courseId),
            studentRegistered: self::studentRegistered($command->studentId),
            studentSubscriptions: self::studentSubscriptions($command->studentId),
        );
        if (!$decisionModel->state->courseExists) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579448);
        }
        if (!$decisionModel->state->studentRegistered) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579463);
        }
        if (!$decisionModel->state->studentSubscriptions->contains($command->courseId)) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because that student is not subscribed to this course', $command->studentId->value, $command->courseId->value), 1684579464);
        }
        $domainEvent = new StudentUnsubscribedFromCourse($command->studentId, $command->courseId);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
    }

    private function handleUpdateCourseCapacity(UpdateCourseCapacity $command): void
    {
        $decisionModel = $this->buildDecisionModel(
            courseExists: self::courseExists($command->courseId),
            courseCapacity: self::courseCapacity($command->courseId),
            numberOfCourseSubscriptions: self::numberOfCourseSubscriptions($command->courseId),
        );
        if (!$decisionModel->state->courseExists) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because a course with that id does not exist', $command->courseId->value, $command->newCapacity->value), 1684604283);
        }
        if ($decisionModel->state->courseCapacity->equals($command->newCapacity)) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because that is already the courses capacity', $command->courseId->value, $command->newCapacity->value), 1686819073);
        }
        if ($decisionModel->state->numberOfCourseSubscriptions > $command->newCapacity->value) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because it already has %d active subscriptions', $command->courseId->value, $command->newCapacity->value, $decisionModel->state->numberOfCourseSubscriptions), 1684604361);
        }
        $domainEvent = new CourseCapacityChanged($command->courseId, $command->newCapacity);
        $this->eventStore->append($this->eventSerializer->convertDomainEvent($domainEvent), $decisionModel->appendCondition);
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
     * @return Projection<int>
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

    /**
     * @return Projection<bool>
     */
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


    // ------------------------------------

    /**
     * @phpstan-ignore-next-line
     */
    private function buildDecisionModel(Projection ...$projections): DecisionModel
    {
        $query = StreamQuery::wildcard();
        Assert::isMap($projections);
        $compositeProjection = CompositeProjection::create($projections);
        $query = $query->withCriteria($compositeProjection->getCriteria());
        $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::none();
        $state = $compositeProjection->initialState();
        foreach ($this->eventStore->read($query) as $eventEnvelope) {
            $domainEvent = $this->eventSerializer->convertEvent($eventEnvelope->event);
            $state = $compositeProjection->apply($state, $domainEvent, $eventEnvelope);
            $expectedHighestSequenceNumber = ExpectedHighestSequenceNumber::fromSequenceNumber($eventEnvelope->sequenceNumber);
        }
        return new DecisionModel($state, new AppendCondition($query, $expectedHighestSequenceNumber));
    }
}
