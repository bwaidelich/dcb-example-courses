<?php
declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Wwwision\DCBEventStore\Exception\ConstraintException;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\Events;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\CommandHandler;
use Wwwision\DCBExample\Event\CourseCreated;
use Wwwision\DCBExample\Event\Normalizer\EventNormalizer;
use Wwwision\DCBExample\Event\StudentRegistered;
use Wwwision\DCBExample\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;
use Wwwision\DCBExample\Model\StudentId;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Helper\InMemoryEventStore;
use PHPUnit\Framework\Assert;
use function array_map;
use function explode;
use function var_dump;

final class FeatureContext implements Context
{
    private EventStore $eventStore;

    private CommandHandler $commandHandler;
    private EventNormalizer $eventNormalizer;

    private ?ConstraintException $lastConstraintException = null;

    public function __construct()
    {
        $this->eventStore = InMemoryEventStore::create();
        $this->commandHandler = new CommandHandler($this->eventStore);
        $this->eventNormalizer = new EventNormalizer();
    }

    /**
     * @AfterScenario
     */
    public function throwConstraintException(): void
    {
        if ($this->lastConstraintException !== null) {
            throw $this->lastConstraintException;
        }
    }

    // -------------- EVENTS ----------------------

    /**
     * @Given course :courseIds exists with the title :courseTitle and a capacity of :initialCapacity
     * @Given course :courseIds exists with a capacity of :initialCapacity
     * @Given course :courseIds exists with the title :courseTitle
     * @Given course(s) :courseIds exist(s)
     */
    public function courseExists(string $courseIds, string $courseTitle = null, int $initialCapacity = null): void
    {
        $domainEvents = [];
        foreach (explode(',', $courseIds) as $courseId) {
            $domainEvents[] = new CourseCreated(
                CourseId::fromString($courseId),
                CourseCapacity::fromInteger($initialCapacity ?? 10),
                CourseTitle::fromString($courseTitle ?? 'Course ' . $courseId),
            );
        }
        $this->appendEvents(...$domainEvents);
    }

    /**
     * @Given student :studentIds is registered
     * @Given students :studentIds are registered
     */
    public function studentIsRegistered(string $studentIds): void
    {
        $domainEvents = [];
        foreach (explode(',', $studentIds) as $studentId) {
            $domainEvents[] = new StudentRegistered(
                StudentId::fromString($studentId),
            );
        }
        $this->appendEvents(...$domainEvents);
    }

    /**
     * @Given student :studentId is subscribed to course(s) :courseIds
     */
    public function studentIsSubscribedToCourses(string $studentId, string $courseIds): void
    {
        $domainEvents = [];
        foreach (explode(',', $courseIds) as $courseId) {
            $domainEvents[] = new StudentSubscribedToCourse(
                CourseId::fromString($courseId),
                StudentId::fromString($studentId),
            );
        }
        $this->appendEvents(...$domainEvents);
    }

    /**
     * @Given student :studentId is unsubscribed from course(s) :courseIds
     */
    public function studentIsUnsubscribedFromCourses(string $studentId, string $courseIds): void
    {
        $domainEvents = [];
        foreach (explode(',', $courseIds) as $courseId) {
            $domainEvents[] = new StudentUnsubscribedFromCourse(StudentId::fromString($studentId), CourseId::fromString($courseId),
            );
        }
        $this->appendEvents(...$domainEvents);
    }

    // -------------- COMMANDS ----------------------

    /**
     * @When a new course is created with id :courseId, title :courseTitle and capacity of :initialCapacity
     * @When a new course is created with id :courseId and capacity of :initialCapacity
     * @When a new course is created with id :courseId and title :courseTitle
     * @When a new course is created with id :courseId
     */
    public function aNewCourseIsCreated(string $courseId, string $courseTitle = null, int $initialCapacity = null): void
    {
        $command = new CreateCourse(
            CourseId::fromString($courseId),
            CourseCapacity::fromInteger($initialCapacity ?? 10),
            CourseTitle::fromString($courseTitle ?? 'Course ' . $courseId),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @When course :courseId is renamed to :newCourseTitle
     */
    public function courseIsRenamed(string $courseId, string $newCourseTitle): void
    {
        $command = new RenameCourse(
            CourseId::fromString($courseId),
            CourseTitle::fromString($newCourseTitle),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @When course :courseId capacity is changed to :newCapacity
     */
    public function courseCapacityIsChanged(string $courseId, int $newCapacity): void
    {
        $command = new UpdateCourseCapacity(
            CourseId::fromString($courseId),
            CourseCapacity::fromInteger($newCapacity),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @When a new student is registered with id :studentId
     */
    public function aNewCourseIsRegistered(string $studentId): void
    {
        $command = new RegisterStudent(
            StudentId::fromString($studentId),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @When student :studentId subscribes to course :courseId
     */
    public function studentSubscribesToCourse(string $studentId, string $courseId): void
    {
        $command = new SubscribeStudentToCourse(
            CourseId::fromString($courseId),
            StudentId::fromString($studentId),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @When student :studentId unsubscribes from course :courseId
     */
    public function studentUnsubscribesFromCourse(string $studentId, string $courseId): void
    {
        $command = new UnsubscribeStudentFromCourse(
            CourseId::fromString($courseId),
            StudentId::fromString($studentId),
        );
        $this->handleCommandAndCatchException($command);
    }

    /**
     * @Then the command should be rejected with the following message:
     */
    public function theCommandShouldBeRejectedWithTheFollowingMessage(PyStringNode $expectedErrorMessage): void
    {
        Assert::assertNotNull($this->lastConstraintException, 'Expected an error, but none was thrown');
        Assert::assertSame($expectedErrorMessage->getRaw(), $this->lastConstraintException->getMessage(), 'Error message did not match the expected');
        $this->lastConstraintException = null;
    }

    /**
     * @Then the command should pass without errors
     */
    public function theCommandShouldPassWithoutErrors(): void
    {
        Assert::assertNull($this->lastConstraintException, 'Expected no error, but one was thrown');
    }

    // ----------------------------

    private function handleCommandAndCatchException(Command $command): void
    {
        try {
            $this->commandHandler->handle($command);
        } catch (ConstraintException $exception) {
            $this->lastConstraintException = $exception;
        }
    }

    private function appendEvents(DomainEvent ...$domainEvents): void
    {
        $convertedEvents = Events::fromArray(array_map($this->eventNormalizer->convertDomainEvent(...), $domainEvents));
        $this->eventStore->append($convertedEvents);
    }


}
