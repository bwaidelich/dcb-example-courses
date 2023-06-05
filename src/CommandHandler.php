<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use RuntimeException;
use Wwwision\DCBEventStore\Aggregate\AggregateLoader;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\Event\Normalizer\EventNormalizer;
use Wwwision\DCBExample\Model\Aggregate\CourseCapacityAggregate;
use Wwwision\DCBExample\Model\Aggregate\CourseExistenceAggregate;
use Wwwision\DCBExample\Model\Aggregate\CourseTitleAggregate;
use Wwwision\DCBExample\Model\Aggregate\StudentExistenceAggregate;
use Wwwision\DCBExample\Model\Aggregate\StudentSubscriptionsAggregate;

use function sprintf;

/**
 * Main authority of this package, responsible to handle incoming {@see Command}s (@see self::handle()}
 */
final readonly class CommandHandler
{
    private AggregateLoader $aggregateLoader;

    public function __construct(private EventStore $eventStore)
    {
        $this->aggregateLoader = new AggregateLoader($this->eventStore, new EventNormalizer());
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
        $this->aggregateLoader->transactional(static function () use ($command, $courseExistenceAggregate) {
            $courseExistenceAggregate->createCourse($command->initialCapacity, $command->courseTitle);
        }, $courseExistenceAggregate);
    }

    private function handleRenameCourse(RenameCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $courseTitleAggregate = new CourseTitleAggregate($command->courseId);
        $this->aggregateLoader->transactional(static function () use ($command, $courseExistenceAggregate, $courseTitleAggregate) {
            if (!$courseExistenceAggregate->courseExists()) {
                throw new ConstraintException(sprintf('Failed to rename course with id "%s" because a course with that id does not exist', $command->courseId->value), 1684509782);
            }
            $courseTitleAggregate->renameCourse($command->newCourseTitle);
        }, $courseExistenceAggregate, $courseTitleAggregate);
    }

    private function handleRegisterStudent(RegisterStudent $command): void
    {
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $this->aggregateLoader->transactional(static function () use ($studentExistenceAggregate) {
            $studentExistenceAggregate->registerStudent();
        }, $studentExistenceAggregate);
    }

    private function handleSubscribeStudentToCourse(SubscribeStudentToCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $courseSubscriptionsAggregate = new CourseCapacityAggregate($command->courseId);
        $studentSubscriptionsAggregate = new StudentSubscriptionsAggregate($command->studentId);

        $this->aggregateLoader->transactional(static function () use ($command, $courseExistenceAggregate, $studentExistenceAggregate, $courseSubscriptionsAggregate, $studentSubscriptionsAggregate) {
            if (!$courseExistenceAggregate->courseExists()) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1685266122);
            }
            if (!$studentExistenceAggregate->studentExists()) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579151);
            }
            if ($courseSubscriptionsAggregate->isCourseCapacityReached()) {
                throw new ConstraintException(sprintf('Failed to subscribe student with id "%s" to course with id "%s" because the course\'s capacity of %d is reached', $command->studentId->value, $command->courseId->value, $courseSubscriptionsAggregate->courseCapacity()->value), 1684603201);
            }
            $studentSubscriptionsAggregate->subscribeToCourse($command->courseId);
        }, $courseExistenceAggregate, $studentExistenceAggregate, $courseSubscriptionsAggregate, $studentSubscriptionsAggregate);
    }

    private function handleUnsubscribeStudentFromCourse(UnsubscribeStudentFromCourse $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $studentExistenceAggregate = new StudentExistenceAggregate($command->studentId);
        $studentSubscriptionsAggregate = new StudentSubscriptionsAggregate($command->studentId);

        $this->aggregateLoader->transactional(static function () use ($command, $courseExistenceAggregate, $studentExistenceAggregate, $studentSubscriptionsAggregate) {
            if (!$courseExistenceAggregate->courseExists()) {
                throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a course with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579448);
            }
            if (!$studentExistenceAggregate->studentExists()) {
                throw new ConstraintException(sprintf('Failed to unsubscribe student with id "%s" from course with id "%s" because a student with that id does not exist', $command->studentId->value, $command->courseId->value), 1684579463);
            }
            $studentSubscriptionsAggregate->unsubscribeFromCourse($command->courseId);
        }, $courseExistenceAggregate, $studentExistenceAggregate, $studentSubscriptionsAggregate);
    }

    private function handleUpdateCourseCapacity(UpdateCourseCapacity $command): void
    {
        $courseExistenceAggregate = new CourseExistenceAggregate($command->courseId);
        $courseSubscriptionsAggregate = new CourseCapacityAggregate($command->courseId);

        $this->aggregateLoader->transactional(static function () use ($command, $courseExistenceAggregate, $courseSubscriptionsAggregate) {
            if (!$courseExistenceAggregate->courseExists()) {
                throw new ConstraintException(sprintf('Failed to change capacity of course with id "%s" to %d because a course with that id does not exist', $command->courseId->value, $command->newCapacity->value), 1684604283);
            }
            $courseSubscriptionsAggregate->changeCourseCapacity($command->newCapacity);
        }, $courseExistenceAggregate, $courseSubscriptionsAggregate);
    }
}
