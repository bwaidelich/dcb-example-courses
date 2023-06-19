<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use RuntimeException;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Model\DomainId;
use Wwwision\DCBEventStore\Model\DomainIds;
use Wwwision\DCBEventStore\Model\EventTypes;
use Wwwision\DCBEventStore\Model\ExpectedLastEventId;
use Wwwision\DCBEventStore\Model\StreamQuery;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\Event\Appender\EventAppender;
use Wwwision\DCBExample\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\CourseRenamed;
use Wwwision\DCBExample\Event\Normalizer\EventNormalizer;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Exception\ConstraintException;
use Wwwision\DCBExample\Model\Aggregate\Aggregate;
use Wwwision\DCBExample\Model\Aggregate\CourseCapacityAggregate;
use Wwwision\DCBExample\Model\Aggregate\CourseExistenceAggregate;
use Wwwision\DCBExample\Model\Aggregate\CourseTitleAggregate;
use Wwwision\DCBExample\Model\Aggregate\StudentExistenceAggregate;
use Wwwision\DCBExample\Model\Aggregate\StudentSubscriptionsAggregate;

use function array_unique;
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
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $appender = $this->reconstituteAggregateStates($courseExistenceAggregate);

        if ($courseExistenceAggregate->courseExists()) {
            throw new ConstraintException(sprintf('Failed to create course with id "%s" because a course with that id already exists', $command->courseId->value), 1684593925);
        }
        $appender->append(new CourseCreated($command->courseId, $command->initialCapacity, $command->courseTitle));
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $courseTitleAggregate = new CourseTitleAggregate($command->courseId);
        $appender = $this->reconstituteAggregateStates($courseExistenceAggregate, $courseTitleAggregate);

        if (!$courseExistenceAggregate->courseExists()) {
            throw new ConstraintException(sprintf('Failed to rename course with id "%s" because a course with that id does not exist', $command->courseId->value), 1684509782);
        }
        if ($courseTitleAggregate->courseTitle !== null && $courseTitleAggregate->courseTitle->equals($command->newCourseTitle)) {
            throw new ConstraintException(sprintf('Failed to rename course with id "%s" to "%s" because this is already the title of this course', $command->courseId->value, $command->newCourseTitle->value), 1684509837);
        }
        $appender->append(new CourseRenamed($command->courseId, $command->newCourseTitle));
    }

    private function handleRegisterStudent(RegisterStudent $command): void
    {
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $appender = $this->reconstituteAggregateStates($studentExistenceAggregate);
        if ($studentExistenceAggregate->studentExists()) {
            throw new ConstraintException(sprintf('Failed to register student with id "%s" because a student with that id already exists', $command->studentId->value), 1684579300);
        }
        $appender->append(new StudentRegistered($command->studentId));
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $courseCapacityAggregate = new CourseCapacityAggregate($command->courseId);
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $studentSubscriptionsAggregate = new StudentSubscriptionsAggregate($command->studentId);
        $appender = $this->reconstituteAggregateStates($courseExistenceAggregate, $courseCapacityAggregate, $studentExistenceAggregate, $studentSubscriptionsAggregate);

        if (!$studentExistenceAggregate->studentExists()) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1686914105);
        }
        if (!$courseExistenceAggregate->courseExists()) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1685266122);
        }
        if ($courseCapacityAggregate->courseCapacityReached()) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because the course\'s capacity of %d is reached', $command->studentId->value, $command->courseId->value, $courseCapacityAggregate->courseCapacity()->value), 1684603201);
        }
        if ($studentSubscriptionsAggregate->subscribedToCourse($command->courseId)) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed to this course', $command->studentId->value, $command->courseId->value), 1684510963);
        }
        $maximumSubscriptionsPerStudent = 10;
        if ($studentSubscriptionsAggregate->numberOfSubscriptions() === $maximumSubscriptionsPerStudent) {
            throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because that student is already subscribed the maximum of %d courses', $command->studentId->value, $command->courseId->value, $maximumSubscriptionsPerStudent), 1684605232);
        }
        $appender->append(new StudentSubscribedToCourse($command->courseId, $command->studentId));
    }

    private function handleUnsubscribeStudentFromCourse(UnsubscribeStudentFromCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $studentSubscriptionsAggregate = new StudentSubscriptionsAggregate($command->studentId);
        $appender = $this->reconstituteAggregateStates($courseExistenceAggregate, $studentExistenceAggregate, $studentSubscriptionsAggregate);

        if (!$courseExistenceAggregate->courseExists()) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579448);
        }
        if (!$studentExistenceAggregate->studentExists()) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579463);
        }
        if (!$studentSubscriptionsAggregate->subscribedToCourse($command->courseId)) {
            throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because that student is not subscribed to this course', $command->studentId->value, $command->courseId->value), 1684579464);
        }
        $appender->append(new StudentUnsubscribedFromCourse($command->studentId, $command->courseId));
    }

    private function handleUpdateCourseCapacity(UpdateCourseCapacity $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $courseCapacityAggregate = new CourseCapacityAggregate($command->courseId);
        $appender = $this->reconstituteAggregateStates($courseExistenceAggregate, $courseCapacityAggregate);

        if (!$courseExistenceAggregate->courseExists()) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because a course with that id does not exist', $command->courseId->value, $command->newCapacity->value), 1684604283);
        }
        if ($command->newCapacity->equals($courseCapacityAggregate->courseCapacity())) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because that is already the courses capacity', $command->courseId->value, $command->newCapacity->value), 1686819073);
        }
        if ($courseCapacityAggregate->numberOfSubscriptions() > $command->newCapacity->value) {
            throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because it already has %d active subscriptions', $command->courseId->value, $command->newCapacity->value, $courseCapacityAggregate->numberOfSubscriptions()), 1684604361);
        }
        $appender->append(new CourseCapacityChanged($command->courseId, $command->newCapacity));
    }

    // -----------------------------

    private function reconstituteAggregateStates(Aggregate ...$aggregates): EventAppender
    {
        $domainIds = [];
        $eventTypes = [];
        foreach ($aggregates as $aggregate) {
            $aggregateDomainIds = $aggregate->domainIds();
            if ($aggregateDomainIds instanceof DomainId) {
                $aggregateDomainIds = DomainIds::create($aggregateDomainIds);
            }
            $domainIds[] = $aggregateDomainIds->toArray();
            $eventTypes[] = $aggregate->eventTypes()->toStringArray();
        }
        $query = StreamQuery::matchingIdsAndTypes(
            DomainIds::fromArray(array_merge(...$domainIds)),
            EventTypes::fromStrings(...array_unique(array_merge(...$eventTypes))),
        );
        $expectedLastEventId = ExpectedLastEventId::none();
        foreach ($this->eventStore->stream($query) as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->convertEvent($eventEnvelope);
            foreach ($aggregates as $aggregate) {
                if ($domainEvent->domainIds()->intersects($aggregate->domainIds())) {
                    $aggregate->apply($domainEvent);
                }
            }
            $expectedLastEventId = ExpectedLastEventId::fromEventId($eventEnvelope->event->id);
        }
        return new EventAppender($this->eventStore, $query, $expectedLastEventId);
    }
}
