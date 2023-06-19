<?php
declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use InvalidArgumentException;
use Wwwision\DCBEventStore\EventStream;
use Wwwision\DCBExample\Exception\ConstraintException;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\Event;
use Wwwision\DCBEventStore\Model\EventEnvelope;
use Wwwision\DCBEventStore\Model\EventId;
use Wwwision\DCBEventStore\Model\Events;
use Wwwision\DCBEventStore\Model\ExpectedLastEventId;
use Wwwision\DCBEventStore\Model\StreamQuery;
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
use function array_diff;
use function array_keys;
use function array_map;
use function explode;
use function implode;
use function json_decode;
use function sprintf;
use function var_dump;
use const JSON_THROW_ON_ERROR;

final class FeatureContext implements Context
{
    private EventStore $eventStore;

    private CommandHandler $commandHandler;
    private EventNormalizer $eventNormalizer;

    private ?ConstraintException $lastConstraintException = null;

    public function __construct()
    {
        $innerEventStore = InMemoryEventStore::create();

        $this->eventStore = new class ($innerEventStore) implements EventStore {

            public Events $appendedEvents;
            public Events $readEvents;

            public function __construct(private EventStore $inner) {
                $this->appendedEvents = Events::none();
                $this->readEvents = Events::none();
            }

            public function setup(): void
            {
                $this->inner->setup();
            }

            public function streamAll(): EventStream
            {
                $innerStream = $this->inner->streamAll();
                foreach ($innerStream as $eventEnvelope) {
                    $this->readEvents = $this->readEvents->append($eventEnvelope->event);
                }
                return $innerStream;
            }

            public function stream(StreamQuery $query): EventStream
            {
                $innerStream = $this->inner->stream($query);
                foreach ($innerStream as $eventEnvelope) {
                    $this->readEvents = $this->readEvents->append($eventEnvelope->event);
                }
                return $innerStream;
            }

            public function append(Events $events): void
            {
                $this->appendedEvents = $events;
                $this->inner->append($events);
            }


            public function conditionalAppend(Events $events, StreamQuery $query, ExpectedLastEventId $expectedLastEventId): void
            {
                $this->inner->conditionalAppend($events, $query, $expectedLastEventId);
                $this->appendedEvents = $events;
            }
        };
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
                courseTitle::fromString($courseTitle ?? 'course ' . $courseId),
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
            courseTitle::fromString($courseTitle ?? 'course ' . $courseId),
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

    /**
     * @Then no events should be read
     */
    public function noEventsShouldBeRead(): void
    {
        Assert::assertCount(0, $this->eventStore->readEvents);
    }

    /**
     * @Then the following event(s) should be read:
     */
    public function theFollowingEventsShouldBeRead(TableNode $expectedEventsTable): void
    {
        self::asserEvents($expectedEventsTable, $this->eventStore->readEvents);
    }


    /**
     * @Then no events should be appended
     */
    public function noEventsShouldBeAppended(): void
    {
        Assert::assertSame(0, $this->eventStore->appendedEvents->count(), 'Expected no events to be appended');
    }

    /**
     * @Then the following event(s) should be appended:
     */
    public function theFollowingEventsShouldBeAppended(TableNode $expectedEventsTable): void
    {
        self::asserEvents($expectedEventsTable, $this->eventStore->appendedEvents);
    }

    private static function asserEvents(TableNode $expectedEventsTable, Events $events): void
    {
        $expectedEvents = array_map(static fn (array $col) => array_map(static fn(string $val) => json_decode($val, true, 512, JSON_THROW_ON_ERROR), $col), $expectedEventsTable->getColumnsHash());
        $actualEvents = [];
        $index = 0;
        foreach ($events as $event) {
            $actualEvents[] = self::eventToArray(isset($expectedEvents[$index]) ? array_keys($expectedEvents[$index]) : ['Id', 'Type', 'Data', 'Domain Ids'], $event);
            $index ++;
        }
        Assert::assertEquals($expectedEvents, $actualEvents);
    }

    private static function eventToArray(array $keys, Event $event): array
    {
        $supportedKeys = ['Id', 'Type', 'Data', 'Domain Ids'];
        $unsupportedKeys = array_diff($keys, $supportedKeys);
        if ($unsupportedKeys !== []) {
            throw new InvalidArgumentException(sprintf('Invalid key(s) "%s" for expected event. Allowed keys are: "%s"', implode('", "', $unsupportedKeys), implode('", "', $supportedKeys)), 1686128517);
        }
        $actualAsArray = [
            'Id' => $event->id->value,
            'Type' => $event->type->value,
            'Data' => json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR),
            'Domain Ids' => $event->domainIds->toArray(),
        ];
        foreach (array_diff($supportedKeys, $keys) as $unusedKey) {
            unset($actualAsArray[$unusedKey]);
        }
        return $actualAsArray;
    }

    // ----------------------------

    private function handleCommandAndCatchException(Command $command): void
    {
        $this->eventStore->appendedEvents = Events::none();
        $this->eventStore->readEvents = Events::none();
        try {
            $this->commandHandler->handle($command);
        } catch (ConstraintException $exception) {
            $this->lastConstraintException = $exception;
        }
    }

    private function appendEvents(DomainEvent ...$domainEvents): void
    {
        $this->eventStore->append(Events::fromArray(array_map($this->eventNormalizer->convertDomainEvent(...), $domainEvents)));
    }


}
