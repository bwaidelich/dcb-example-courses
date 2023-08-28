<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\DCBExample\App;
use Wwwision\DCBExample\Commands\Command;
use Wwwision\DCBExample\Commands\CreateCourse;
use Wwwision\DCBExample\Commands\RegisterStudent;
use Wwwision\DCBExample\Commands\RenameCourse;
use Wwwision\DCBExample\Commands\SubscribeStudentToCourse;
use Wwwision\DCBExample\Commands\UnsubscribeStudentFromCourse;
use Wwwision\DCBExample\Commands\UpdateCourseCapacity;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\DCBExample\Types\StudentId;

require __DIR__ . '/vendor/autoload.php';

/** We use an in-memory SQLite database for the events (@see https://www.doctrine-project.org/projects/doctrine-dbal/en/2.4/reference/configuration.html for how to configure other database backends) **/
$connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

/** The second parameter is the table name to store the events in **/
$eventStore = DoctrineEventStore::create($connection, 'dcb_events');

/** The {@see EventStore::setup()} method is used to make sure that the Events Store backend is set up (i.e. required tables are created and their schema up-to-date) **/
$eventStore->setup();

/** @var {@see App} is the central authority to handle {@see Command}s */
$commandHandler = new App($eventStore);

// Example:
// 1. Create a course (c1)
$commandHandler->handle(new CreateCourse(CourseId::fromString('c1'), CourseCapacity::fromInteger(10), CourseTitle::fromString('Course 02')));

// 2. rename it, register a student (s1) and subscribe it to the course, change the course capacity, unregister the student
$commandHandler->handle(new RenameCourse(CourseId::fromString('c1'), CourseTitle::fromString('Course 01 renamed again')));

// 3. register a student (s1) in the system
$commandHandler->handle(new RegisterStudent(StudentId::fromString('s1')));

// 4. subscribe student (s1) to course (s1)
$commandHandler->handle(new SubscribeStudentToCourse(CourseId::fromString('c1'), StudentId::fromString('s1')));

// 5. change capacity of course (c1) to 5
$commandHandler->handle(new UpdateCourseCapacity(CourseId::fromString('c1'), CourseCapacity::fromInteger(5)));

// 6. unsubscribe student (s1) from course (c1)
$commandHandler->handle(new UnsubscribeStudentFromCourse(CourseId::fromString('c1'), StudentId::fromString('s1')));
