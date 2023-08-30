<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
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
use Wwwision\DCBLibraryDoctrine\DbalCheckpointStorage;

require __DIR__ . '/vendor/autoload.php';

$dsn = $argv[1] ?? 'sqlite:///:memory:';
$connection = DriverManager::getConnection(['url' => $dsn]);

/** @var {@see App} is the central authority to handle {@see Command}s */
$app = new App($connection);

// Example:
// 1. Create a course (c1)
$app->handle(new CreateCourse(CourseId::fromString('c1'), CourseCapacity::fromInteger(10), CourseTitle::fromString('Course 01')));
$app->handle(new CreateCourse(CourseId::fromString('c2'), CourseCapacity::fromInteger(15), CourseTitle::fromString('Course 02')));

// 2. rename it, register a student (s1) and subscribe it to the course, change the course capacity, unregister the student
$app->handle(new RenameCourse(CourseId::fromString('c1'), CourseTitle::fromString('Course 01 renamed again')));

// 3. register a student (s1) in the system
$app->handle(new RegisterStudent(StudentId::fromString('s1')));

// 4. subscribe student (s1) to course (s1)
$app->handle(new SubscribeStudentToCourse(CourseId::fromString('c1'), StudentId::fromString('s1')));
$app->handle(new SubscribeStudentToCourse(CourseId::fromString('c2'), StudentId::fromString('s1')));

// 5. change capacity of course (c1) to 5
$app->handle(new UpdateCourseCapacity(CourseId::fromString('c1'), CourseCapacity::fromInteger(5)));

// 6. unsubscribe student (s1) from course (c1)
$app->handle(new UnsubscribeStudentFromCourse(CourseId::fromString('c1'), StudentId::fromString('s1')));
