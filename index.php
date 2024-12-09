<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\CommandHandler;
use Wwwision\DCBExample\Command\Command;
use Wwwision\DCBExample\Command\CreateCourse;
use Wwwision\DCBExample\Command\RegisterStudent;
use Wwwision\DCBExample\Command\RenameCourse;
use Wwwision\DCBExample\Command\SubscribeStudentToCourse;
use Wwwision\DCBExample\Command\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Command\UpdateCourseCapacity;

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

/** @var {@see CommandHandler} is the central authority to handle {@see Command}s */
$commandHandler = new CommandHandler($eventStore);

// Example:
// 1. Create a course (c1)
$commandHandler->handle(CreateCourse::create(courseId: 'c1', initialCapacity: 10, courseTitle: 'Course 02'));

// 2. rename it, register a student (s1) and subscribe it to the course, change the course capacity, unregister the student
$commandHandler->handle(RenameCourse::create(courseId: 'c1', newCourseTitle: 'Course 01 renamed'));

// 3. register a student (s1) in the system
$commandHandler->handle(RegisterStudent::create(studentId: 's1'));

// 4. subscribe student (s1) to course (s1)
$commandHandler->handle(SubscribeStudentToCourse::create(courseId: 'c1', studentId: 's1'));

// 5. change capacity of course (c1) to 5
$commandHandler->handle(UpdateCourseCapacity::create(courseId: 'c1', newCapacity: 5));

// 6. unsubscribe student (s1) from course (c1)
$commandHandler->handle(UnsubscribeStudentFromCourse::create(courseId: 'c1', studentId: 's1'));