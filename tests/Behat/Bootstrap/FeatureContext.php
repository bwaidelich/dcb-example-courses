<?php
declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use Wwwision\DCBEventStore\AppendCondition\AppendCondition;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\Event\Events;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\ReadOptions;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvents;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;
use Wwwision\DCBExample\Domain\App;
use Wwwision\DCBExample\Domain\Event\CourseDefined;
use Wwwision\DCBExample\Domain\Event\StudentRegistered;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Types\CourseCapacity;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseTitle;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DomainEvent;
use Wwwision\DCBExample\Infrastructure\EventSerializer;
use Wwwision\DCBExample\Infrastructure\Exception\ConstraintException;

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
    private EventSerializer $eventSerializer;

    private ?ConstraintException $lastConstraintException = null;

    public function __construct(string|null $eventStoreDsn = null, private string $eventTableName = 'dcb_events_test')
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

            public function read(Query $query, ReadOptions|null $options = null): SequencedEvents
            {
                $innerStream = $this->inner->read($query, $options);
                return SequencedEvents::create(function () use ($innerStream) {
                    foreach ($innerStream as $sequencedEvent) {
                        $this->readEvents = $this->readEvents->append($sequencedEvent->event);
                        yield $sequencedEvent;
                    }
                });
            }

            public function append(Events|Event $events, AppendCondition|null $condition = null): void
            {
                $this->inner->append($events, $condition);
                if ($events instanceof Event) {
                    $events = Events::fromArray([$events]);
                }
                $this->appendedEvents = $events;
            }
        };
        $this->app = new App($this->eventStore);
        $this->eventSerializer = new EventSerializer('\\Wwwision\\DCBExample\\Domain\\Event');
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
     * AfterScenario
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
    public function courseExists(string $courseIds, string|null $courseTitle = null, int|null $initialCapacity = null): void
    {
        $domainEvents = [];
        foreach (explode(',', $courseIds) as $courseId) {
            $domainEvents[] = new CourseDefined(
                CourseId::fromString($courseId),
                CourseCapacity::fromInteger($initialCapacity ?? 10),
                courseTitle::fromString($courseTitle ?? ('course ' . $courseId)),
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
     * @When a new course is defined with id :courseId, title :courseTitle and capacity of :initialCapacity
     * @When a new course is defined with id :courseId and capacity of :initialCapacity
     * @When a new course is defined with id :courseId and title :courseTitle
     * @When a new course is defined with id :courseId
     */
    public function aNewCourseIsDefined(string $courseId, string $courseTitle = 'Course Title', int $initialCapacity = 10): void
    {
        $this->tryAndCatchException(fn () => $this->app->defineCourse(
            CourseId::fromString($courseId),
            CourseTitle::fromString($courseTitle),
            CourseCapacity::fromInteger($initialCapacity)
        ));
    }

    /**
     * @When course :courseId is renamed to :newCourseTitle
     */
    public function courseIsRenamed(string $courseId, string $newCourseTitle): void
    {
        $this->tryAndCatchException(fn () => $this->app->renameCourse(
            CourseId::fromString($courseId),
            CourseTitle::fromString($newCourseTitle),
        ));
    }

    /**
     * @When course :courseId capacity is changed to :newCapacity
     */
    public function courseCapacityIsChanged(string $courseId, int $newCapacity): void
    {
        $this->tryAndCatchException(fn () => $this->app->changeCourseCapacity(
            CourseId::fromString($courseId),
            CourseCapacity::fromInteger($newCapacity),
        ));
    }

    /**
     * @When a new student is registered with id :studentId
     */
    public function aNewStudentIsRegistered(string $studentId): void
    {
        $this->tryAndCatchException(fn () => $this->app->registerStudent(
            StudentId::fromString($studentId),
        ));
    }

    /**
     * @When student :studentId subscribes to course :courseId
     */
    public function studentSubscribesToCourse(string $studentId, string $courseId): void
    {
        $this->tryAndCatchException(fn () => $this->app->subscribeStudentToCourse(
            StudentId::fromString($studentId),
            CourseId::fromString($courseId),
        ));
    }

    /**
     * @When student :studentId unsubscribes from course :courseId
     */
    public function studentUnsubscribesFromCourse(string $studentId, string $courseId): void
    {
        $this->tryAndCatchException(fn () => $this->app->unsubscribeStudentFromCourse(
            StudentId::fromString($studentId),
            CourseId::fromString($courseId),
        ));
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
        $supportedKeys = ['Type', 'Data', 'Tags'];
        $unsupportedKeys = array_diff($keys, $supportedKeys);
        if ($unsupportedKeys !== []) {
            throw new InvalidArgumentException(sprintf('Invalid key(s) "%s" for expected event. Allowed keys are: "%s"', implode('", "', $unsupportedKeys), implode('", "', $supportedKeys)), 1686128517);
        }
        $actualAsArray = [
            'Type' => $event->type->value,
            'Data' => json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR),
            'Tags' => $event->tags->toStrings(),
        ];
        foreach (array_diff($supportedKeys, $keys) as $unusedKey) {
            unset($actualAsArray[$unusedKey]);
        }
        return $actualAsArray;
    }

    // ----------------------------

    private function tryAndCatchException(Closure $handler): void
    {
        $this->eventStore->appendedEvents = Events::none();
        $this->eventStore->readEvents = Events::none();
        try {
            $handler();
        } catch (ConstraintException $exception) {
            $this->lastConstraintException = $exception;
        }
    }

    private function appendEvents(DomainEvent ...$domainEvents): void
    {
        $this->eventStore->append(Events::fromArray(array_map($this->eventSerializer->convertDomainEvent(...), $domainEvents)));
    }


}
