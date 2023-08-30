<?php
declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\EventStream;
use Wwwision\DCBEventStore\Helpers\InMemoryEventStream;
use Wwwision\DCBEventStore\Types\AppendCondition;
use Wwwision\DCBEventStore\Types\Event;
use Wwwision\DCBEventStore\Types\Events;
use Wwwision\DCBEventStore\Types\SequenceNumber;
use Wwwision\DCBEventStore\Types\StreamQuery\StreamQuery;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\App;
use Wwwision\DCBExample\Commands\Command;
use Wwwision\DCBExample\Commands\CreateCourse;
use Wwwision\DCBExample\Commands\RegisterStudent;
use Wwwision\DCBExample\Commands\RenameCourse;
use Wwwision\DCBExample\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Commands\UpdateCourseCapacity;
use Wwwision\DCBExample\Events\CourseCreated;
use Wwwision\DCBExample\Events\StudentRegistered;
use Wwwision\DCBExample\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\EventSerializer;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;
use Wwwision\DCBLibrary\DomainEvent;
use Wwwision\DCBLibrary\Exceptions\ConstraintException;
use function array_diff;
use function array_keys;
use function array_map;
use function explode;
use function implode;
use function json_decode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

final class FeatureContext implements Context
{
    private Connection $eventStoreConnection;
    private EventStore $eventStore;

    private App $app;
    private EventSerializer $eventNormalizer;

    private ?ConstraintException $lastConstraintException = null;

    public function __construct(string $eventStoreDsn = null, private string $eventTableName = 'dcb_events_test')
    {
        $this->eventStoreConnection = DriverManager::getConnection(['url' => $eventStoreDsn ?? 'pdo-sqlite://:memory:']);

        /** The second parameter is the table name to store the events in **/
        $innerEventStore = DoctrineEventStore::create($this->eventStoreConnection, $eventTableName);
        $innerEventStore->setup();
        $this->resetEventStore();
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

            public function read(StreamQuery $query, ?SequenceNumber $from = null): EventStream
            {
                $innerStream = $this->inner->read($query, $from);
                $eventEnvelopes = [];
                foreach ($innerStream as $eventEnvelope) {
                    $this->readEvents = $this->readEvents->append($eventEnvelope->event);
                    $eventEnvelopes[] = $eventEnvelope;
                }
                return InMemoryEventStream::create(...$eventEnvelopes);

            }

            public function readBackwards(StreamQuery $query, ?SequenceNumber $from = null): EventStream
            {
                $innerStream = $this->inner->readBackwards($query, $from);
                $eventEnvelopes = [];
                foreach ($innerStream as $eventEnvelope) {
                    $this->readEvents = $this->readEvents->append($eventEnvelope->event);
                    $eventEnvelopes[] = $eventEnvelope;
                }
                return InMemoryEventStream::create(...$eventEnvelopes);
            }

            public function append(Events $events, AppendCondition $condition): void
            {
                $this->inner->append($events, $condition);
                $this->appendedEvents = $events;
            }
        };
        $this->app = new App($this->eventStore);
        $this->eventNormalizer = new EventSerializer();
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

    /**
     * @AfterScenario
     */
    public function resetEventStore(): void
    {
        if ($this->eventStoreConnection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->eventStoreConnection->executeStatement('TRUNCATE TABLE ' . $this->eventTableName . ' RESTART IDENTITY');
        } elseif ($this->eventStoreConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            /** @noinspection SqlWithoutWhere */
            $this->eventStoreConnection->executeStatement('DELETE FROM ' . $this->eventTableName);
            $this->eventStoreConnection->executeStatement('DELETE FROM sqlite_sequence WHERE name =\'' . $this->eventTableName . '\'');
        } else {
            $this->eventStoreConnection->executeStatement('TRUNCATE TABLE ' . $this->eventTableName);
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
            $actualEvents[] = self::eventToArray(isset($expectedEvents[$index]) ? array_keys($expectedEvents[$index]) : ['Id', 'Type', 'Data', 'Tags'], $event);
            $index ++;
        }
        Assert::assertEquals($expectedEvents, $actualEvents);
    }

    private static function eventToArray(array $keys, Event $event): array
    {
        $supportedKeys = ['Id', 'Type', 'Data', 'Tags'];
        $unsupportedKeys = array_diff($keys, $supportedKeys);
        if ($unsupportedKeys !== []) {
            throw new InvalidArgumentException(sprintf('Invalid key(s) "%s" for expected event. Allowed keys are: "%s"', implode('", "', $unsupportedKeys), implode('", "', $supportedKeys)), 1686128517);
        }
        $actualAsArray = [
            'Id' => $event->id->value,
            'Type' => $event->type->value,
            'Data' => json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR),
            'Tags' => $event->tags->toSimpleArray(),
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
            $this->app->handle($command);
        } catch (ConstraintException $exception) {
            $this->lastConstraintException = $exception;
        }
    }

    private function appendEvents(DomainEvent ...$domainEvents): void
    {
        $this->eventStore->append(Events::fromArray(array_map($this->eventNormalizer->convertDomainEvent(...), $domainEvents)), AppendCondition::noConstraints());
    }


}
