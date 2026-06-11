<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course;

use Wwwision\DCBEventStore\Event\Tags;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseCapacityChanged;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseDefined;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRenamed;
use Wwwision\DCBExample\Features\DefineCourse\Events\CourseRescheduled;
use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Course\Dto\CourseSchedule;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;
use Wwwision\DCBExample\Model\Student\Dto\StudentIds;
use Wwwision\DCBTools\Projection\AtomicProjection;
use Wwwision\DCBTools\Projection\Projection;

final readonly class CourseProjections
{
    /**
     * This class only contains static members and is not meant to be initialized
     */
    private function __construct()
    {
    }

    /**
     * @return Projection<bool>
     */
    public static function idIsUsed(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: false)
            ->when(CourseDefined::class, true)
            ;
    }

    /**
     * @return Projection<CourseCapacity>
     */
    public static function capacity(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: CourseCapacity::fromInteger(0))
            ->when(CourseDefined::class, fn($_, CourseDefined $event) => $event->initialCapacity)
            ->when(CourseCapacityChanged::class, fn($_, CourseCapacityChanged $event) => $event->newCapacity)
            ;
    }

    /**
     * @return Projection<int>
     */
    public static function numberOfSubscriptions(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: 0)
            ->when(StudentSubscribedToCourse::class, fn(int $state) => $state + 1)
            ->when(StudentUnsubscribedFromCourse::class, fn(int $state) => $state - 1)
            ;
    }

    /**
     * @return Projection<CourseTitle>
     */
    public static function title(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: CourseTitle::fromString(''))
            ->when(CourseDefined::class, static fn ($_, CourseDefined $event) => $event->courseTitle)
            ->when(CourseRenamed::class, static fn ($_, CourseRenamed $event) => $event->newTitle)
            ;
    }

    /**
     * @return Projection<CourseSchedule|null>
     */
    public static function schedule(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: null)
            ->when(CourseDefined::class, static fn ($_, CourseDefined $event) => $event->schedule)
            ;
    }

    /**
     * @return Projection<CourseIds>
     */
    public static function coursesWithConflictingSchedule(CourseId $courseIdToIgnore, CourseSchedule $schedule): Projection
    {
        // TODO introduce tags for time ranges to reduce the number of events to look up?
        return AtomicProjection::create(Tags::create(), initialState: CourseIds::none())
            ->when(CourseDefined::class, static fn (CourseIds $state, CourseDefined $event) => !$event->courseId->equals($courseIdToIgnore) && $event->schedule->overlaps($schedule) ? $state->with($event->courseId) : $state)
            ->when(CourseRescheduled::class, static fn (CourseIds $state, CourseRescheduled $event) => !$event->courseId->equals($courseIdToIgnore) && $event->newSchedule->overlaps($schedule) ? $state->with($event->courseId) : $state)
            ;
    }

    /**
     * @return Projection<StudentIds>
     */
    public static function subscribedStudents(CourseId|CourseIds $courseIds): Projection
    {
        if ($courseIds instanceof CourseId) {
            $courseIds = CourseIds::create($courseIds);
        }
        return AtomicProjection::create($courseIds, initialState: StudentIds::none())
            ->when(StudentSubscribedToCourse::class, static fn (StudentIds $state, StudentSubscribedToCourse $event) => $courseIds->isEmpty() ? $state : $state->with($event->studentId))
            ->when(StudentUnsubscribedFromCourse::class, static fn (StudentIds $state, StudentUnsubscribedFromCourse $event) => $courseIds->isEmpty() ? $state : $state->without($event->studentId))
            ;
    }
}
