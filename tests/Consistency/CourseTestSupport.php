<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

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
use Wwwision\DCBTools\Serialization\SimpleEventSerializer;
use Wwwision\DCBTools\Testing\Defaults;

/**
 * Shared test wiring: the event serializer and the type-keyed {@see Defaults} for the course domain. The defaults power
 * both the GWT builders ({@see ConsistencyTestCase::build()}) and the {@see \Wwwision\DCBTools\Testing\HandlerBoundaryScanner}.
 */
final class CourseTestSupport
{
    public static function serializer(): SimpleEventSerializer
    {
        return new SimpleEventSerializer([
            CourseDefined::class,
            CourseRescheduled::class,
            CourseRenamed::class,
            CourseCapacityChanged::class,
            StudentRegistered::class,
            StudentSubscribedToCourse::class,
            StudentUnsubscribedFromCourse::class,
        ]);
    }

    public static function defaults(): Defaults
    {
        return Defaults::create()
            ->with(CourseId::class, static fn() => CourseId::fromString('c1'))
            ->with(StudentId::class, static fn() => StudentId::fromString('s1'))
            ->with(CourseSchedule::class, static fn() => CourseSchedule::fromArray(['start' => '2026-01-01 10:00:00', 'end' => '2026-01-01 11:00:00']))
            ->with(CourseCapacity::class, static fn() => CourseCapacity::fromInteger(10))
            ->with(CourseTitle::class, static fn() => CourseTitle::fromString('Course Title'));
    }
}
