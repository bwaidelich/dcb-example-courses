<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\Adapters\DbalCourseProjectionAdapter;
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
use Wwwision\DCBExample\ReadModel\Course\CourseProjection;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseIds;
use Wwwision\DCBExample\Types\CourseState;
use Wwwision\DCBExample\Types\CourseStateValue;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;
use Wwwision\DCBLibrary\Adapters\SynchronousCatchUpQueue;
use Wwwision\DCBLibrary\EventHandling\EventHandlers;
use Wwwision\DCBLibrary\EventHandling\ProjectionEventHandler;
use Wwwision\DCBLibrary\EventPublisher;
use Wwwision\DCBLibrary\Exceptions\ConstraintException;
use Wwwision\DCBLibrary\Projection\CompositeProjection;
use Wwwision\DCBLibrary\Projection\InMemoryProjection;
use Wwwision\DCBLibrary\Projection\Projection;
use Wwwision\DCBLibraryDoctrine\DbalCheckpointStorage;
use function sprintf;

/**
 * Main authority of this package, responsible to handle incoming {@see Command}s (@see self::handle()}
 */
final readonly class App
{
    private EventPublisher $eventPublisher;

    public function __construct(Connection $connection)
    {

        /** The second parameter is the table name to store the events in **/
        $eventStore = DoctrineEventStore::create($connection, 'dcb_events');

        /** The {@see EventStore::setup()} method is used to make sure that the Events Store backend is set up (i.e. required tables are created and their schema up-to-date) **/
        $eventStore->setup();
        $connection->executeStatement('TRUNCATE TABLE dcb_events');

        $eventSerializer = new EventSerializer();

        $courseProjection = new CourseProjection(new DbalCourseProjectionAdapter($connection));
        $courseProjection->setup();
        $courseProjection->reset();

        $courseProjectionEventHandler = new ProjectionEventHandler($courseProjection, new DbalCheckpointStorage($connection, 'dcb_checkpoints', $courseProjection::class), $eventSerializer);
        $courseProjectionEventHandler->setup();
        $courseProjectionEventHandler->reset();

        $eventHandlers = EventHandlers::create()
            ->with('CourseProjection', $courseProjectionEventHandler);


        $this->eventPublisher = new EventPublisher($eventStore, $eventSerializer, new SynchronousCatchUpQueue($eventStore, $eventHandlers));
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
        $this->eventPublisher->conditionalAppend(new CourseStateProjection($command->courseId), function (CourseState $state) use ($command) {
            if ($state->value !== CourseStateValue::NON_EXISTING) {
                throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $command->courseId->value), 1684593925);
            }
            return new CourseCreated($command->courseId, $command->initialCapacity, $command->courseTitle);
        });
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $this->eventPublisher->conditionalAppend(CompositeProjection::create([
            'courseState' => new CourseStateProjection($command->courseId),
            'courseTitle' => self::courseTitle($command->courseId),
        ]), function ($state) use ($command) {
            if ($state->courseState->value === CourseStateValue::NON_EXISTING) {
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
        $this->eventPublisher->conditionalAppend(self::studentRegistered($command->studentId), function (bool $studentRegistered) use ($command) {
            if ($studentRegistered) {
                throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $command->studentId->value), 1684579300);
            }
            return new StudentRegistered($command->studentId);
        });
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $this->eventPublisher->conditionalAppend(CompositeProjection::create([
            'studentRegistered' => self::studentRegistered($command->studentId),
            'courseState' => new CourseStateProjection($command->courseId),
            'studentSubscriptions' => self::studentSubscriptions($command->studentId),
        ]), function ($state) use ($command) {
            if (!$state->studentRegistered) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1686914105);
            }
            if ($state->courseState->value === CourseStateValue::NON_EXISTING) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1685266122);
            }
            if ($state->courseState->value === CourseStateValue::FULLY_BOOKED) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because the course\'s capacity of %d is reached', $command->studentId->value, $command->courseId->value, $state->courseState->capacity->value), 1684603201);
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
        $this->eventPublisher->conditionalAppend(CompositeProjection::create([
            'courseState' => new CourseStateProjection($command->courseId),
            'studentRegistered' => self::studentRegistered($command->studentId),
            'studentSubscriptions' => self::studentSubscriptions($command->studentId),
        ]), function ($state) use ($command) {
            if ($state->courseState->value === CourseStateValue::NON_EXISTING) {
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
        $this->eventPublisher->conditionalAppend(new CourseStateProjection($command->courseId), function (CourseState $state) use ($command) {
            if ($state->value === CourseStateValue::NON_EXISTING) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because a course with that id does not exist', $command->courseId->value, $command->newCapacity->value), 1684604283);
            }
            if ($command->newCapacity->equals($state->capacity)) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because that is already the courses capacity', $command->courseId->value, $command->newCapacity->value), 1686819073);
            }
            if ($state->numberOfSubscriptions > $command->newCapacity->value) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because it already has %d active subscriptions', $command->courseId->value, $command->newCapacity->value, $state->numberOfSubscriptions), 1684604361);
            }
            return new CourseCapacityChanged($command->courseId, $command->newCapacity);
        });
    }

    // -----------------------------

    /**
     * @return Projection<CourseTitle>
     */
    private static function courseTitle(CourseId $courseId): Projection
    {
        return InMemoryProjection::create(
            Tags::create($courseId->toTag()),
            [
                CourseCreated::class => static fn ($_, CourseCreated $event) => $event->courseTitle,
                CourseRenamed::class => static fn ($_, CourseRenamed $event) => $event->newCourseTitle,
            ],
            CourseTitle::fromString('')
        );
    }

    /**
     * @return Projection<bool>
     */
    private static function studentRegistered(StudentId $studentId): Projection
    {
        return InMemoryProjection::create(
            Tags::create($studentId->toTag()),
            [
                StudentRegistered::class => static fn () => true,
            ],
            false
        );
    }

    /**
     * @return Projection<CourseIds>
     */
    private static function studentSubscriptions(StudentId $studentId): Projection
    {
        return InMemoryProjection::create(
            Tags::create($studentId->toTag()),
            [
                StudentSubscribedToCourse::class => static fn (CourseIds $state, StudentSubscribedToCourse $event) => $state->with($event->courseId),
                StudentUnsubscribedFromCourse::class => static fn (CourseIds $state, StudentUnsubscribedFromCourse $event) => $state->without($event->courseId),
            ],
            CourseIds::none(),
        );
    }
}
