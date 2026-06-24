<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Wwwision\DCBEventStore\AppendCondition\AppendCondition;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\Event\Events;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\ReadOptions;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvents;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\App;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRenamed;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRescheduled;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;
use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseSchedule;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBTools\DomainEventAppender;
use Wwwision\DCBTools\Serialization\SimpleEventSerializer;
use Wwwision\DCBTools\StateProjector;

require __DIR__ . '/vendor/autoload.php';

/** We use an in-memory SQLite database for the events **/
$dsn = 'sqlite:///:memory:';

/** @see https://www.doctrine-project.org/projects/doctrine-dbal/en/2.4/reference/configuration.html for how to configure other database backends, some examples: */
#$dsn = 'sqlite:///events.sqlite';
#$dsn = 'mysql://user:password@127.0.0.1:3306/test';
#$dsn = 'pgsql://user:password@127.0.0.1:5432/db';
$connection = DriverManager::getConnection(['url' => $dsn]);

/** The second parameter is the table name to store the events in **/
$eventStore = DoctrineEventStore::create($connection, 'dcb_events');

$eventStoreWithTracer = new class ($eventStore) implements EventStore {

    private bool $active = false;

    private array $messages = [];

    public function __construct(private EventStore $inner) {}

    public function start(string $message): void
    {
        $this->messages[] = $message;
        $this->active = true;
    }

    public function end(): string
    {
        $this->active = false;
        $result = implode(PHP_EOL, $this->messages);
        $this->messages = [];
        return $result;
    }

    public function read(Query $query, ?ReadOptions $options = null): SequencedEvents
    {
        return $this->inner->read($query, $options);
    }

    public function append(Event|Events $events, ?AppendCondition $condition = null): void
    {
        if ($this->active) {
            $this->messages[] = json_encode($condition->failIfEventsMatch, JSON_PRETTY_PRINT);
        }
        $this->inner->append($events, $condition);
    }
};

/** The {@see EventStore::setup()} method is used to make sure that the Events Store backend is set up (i.e. required tables are created and their schema up-to-date) **/
$eventStore->setup();

$eventSerializer = new SimpleEventSerializer([
    CourseCapacityChanged::class,
    CourseDefined::class,
    CourseRenamed::class,
    CourseRescheduled::class,
    StudentRegistered::class,
    StudentSubscribedToCourse::class,
    StudentUnsubscribedFromCourse::class,
]);

$domainEventAppender = new DomainEventAppender($eventStoreWithTracer, $eventSerializer);
$stateProjector = new StateProjector($eventStoreWithTracer, $eventSerializer);

/** @var {@see App} is the central authority to handle {@see Command}s */
$app = new App($domainEventAppender, $stateProjector);

// Example:
// 1. Define a course (c1)
$app->defineCourse('c1', 10, 'Course 01', CourseSchedule::fromArray(['start' => '2026-08-01 15:30:00', 'end' => '2026-08-01 17:30:00']));
$app->defineCourse('c2', 8, 'Course 02', CourseSchedule::fromArray(['start' => '2026-08-01 17:00:00', 'end' => '2026-08-01 18:30:00']));
$app->defineCourse('c3', 7, 'Course 03', CourseSchedule::fromArray(['start' => '2026-08-01 17:30:00', 'end' => '2026-08-01 18:45:00']));

// 2. rename it
$app->renameCourse('c1', 'Course 01 renamed');

// 3. register a student (s1) in the system
$app->registerStudent('s1');
$app->registerStudent('s2');

// 4. subscribe student (s1) to course (s1)
#$eventStoreWithTracer->start('subscribeStudentToCourse s1 -> c1');
$app->subscribeStudentToCourse('s1', 'c1');
#echo $eventStoreWithTracer->end();
$app->subscribeStudentToCourse('s1', 'c3');
#$app->subscribeStudentToCourse('s2', 'c2');

$app->rescheduleCourse('c3', CourseSchedule::fromArray(['start' => '2026-08-01 14:00:00', 'end' => '2026-08-01 15:30:00']));
#$app->rescheduleCourse('c3', CourseSchedule::fromArray(['start' => '2026-08-01 17:30:00', 'end' => '2026-08-01 19:45:00']));

// 5. change capacity of course (c1) to 5
$app->changeCourseCapacity('c1', 5);

// 6. unsubscribe student (s1) from course (c1)
$app->unsubscribeStudentFromCourse('s1', 'c1');


//foreach ($eventStore->read(Query::all()) as $eventEnvelope) {
//    echo $eventEnvelope->event->type . ': ' . implode(', ', $eventEnvelope->event->tags->toStrings()). PHP_EOL;
//}