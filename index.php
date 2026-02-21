<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\Domain\App;
use Wwwision\DCBExample\Domain\Types\CourseCapacity;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseTitle;
use Wwwision\DCBExample\Domain\Types\StudentId;

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

/** The {@see EventStore::setup()} method is used to make sure that the Events Store backend is set up (i.e. required tables are created and their schema up-to-date) **/
$eventStore->setup();

/** @var {@see App} is the central authority to handle {@see Command}s */
$app = new App($eventStore);

// Example:
// 1. Define a course (c1)
$app->defineCourse(CourseId::fromString('c1'), CourseTitle::fromString('Course 01'), CourseCapacity::fromInteger(10));

// 2. rename it
$app->renameCourse(CourseId::fromString('c1'), CourseTitle::fromString('Course 01 renamed'));

// 3. register a student (s1) in the system
$app->registerStudent(StudentId::fromString('s1'));

// 4. subscribe student (s1) to course (s1)
$app->subscribeStudentToCourse(StudentId::fromString('s1'), CourseId::fromString('c1'));

// 5. change capacity of course (c1) to 5
$app->changeCourseCapacity(CourseId::fromString('c1'), CourseCapacity::fromInteger(5));

// 6. unsubscribe student (s1) from course (c1)
$app->unsubscribeStudentFromCourse(StudentId::fromString('s1'), CourseId::fromString('c1'));