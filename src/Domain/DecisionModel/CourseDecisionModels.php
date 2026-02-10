<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\DecisionModel;

use stdClass;
use Wwwision\DCBExample\Domain\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Domain\Event\CourseDefined;
use Wwwision\DCBExample\Domain\Event\CourseRenamed;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Types\CourseCapacity;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseTitle;
use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraint;
use Wwwision\DCBExample\Infrastructure\Projection\AtomicProjection;
use Wwwision\DCBExample\Infrastructure\Projection\CompositeProjection;
use Wwwision\DCBExample\Infrastructure\Projection\Projection;

final readonly class CourseDecisionModels
{
    /**
     * This class only contains static members and is not meant to be initialized
     */
    private function __construct()
    {
    }

    public static function exists(CourseId $courseId): Constraint
    {
        $projection = AtomicProjection::create($courseId, initialState: false)
            ->when(CourseDefined::class, true);

        return Constraint::create('courseExists', $projection, static fn (bool $state) => $state);
    }

    /**
     * @return Projection<CourseCapacity>
     */
    private static function capacity(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: CourseCapacity::fromInteger(0))
            ->when(CourseDefined::class, fn($_, CourseDefined $event) => $event->initialCapacity)
            ->when(CourseCapacityChanged::class, fn($_, CourseCapacityChanged $event) => $event->newCapacity)
        ;
    }

    public static function capacityEquals(CourseId $courseId, CourseCapacity $candidate): Constraint
    {
        return Constraint::create(
            'courseCapacityEquals',
            self::capacity($courseId),
            static fn (CourseCapacity $currentCapacity) => $currentCapacity->equals($candidate)
        );
    }

    public static function hasFreeSeats(CourseId $courseId): Constraint
    {
        /** @var CompositeProjection<object{courseCapacity: CourseCapacity, numberOfCourseSubscriptions: int}> $projection */
        $projection = CompositeProjection::create([
            'courseCapacity' => self::capacity($courseId),
            'numberOfCourseSubscriptions' => self::numberOfSubscriptions($courseId),
        ], stdClass::class);
        return Constraint::create(
            'courseHasCapacity',
            $projection,
            static fn (object $state) => $state->courseCapacity->value > $state->numberOfCourseSubscriptions
        );
    }

    /**
     * @return Projection<int>
     */
    private static function numberOfSubscriptions(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: 0)
            ->when(StudentSubscribedToCourse::class, fn(int $state) => $state + 1)
            ->when(StudentUnsubscribedFromCourse::class, fn(int $state) => $state - 1)
        ;
    }

    public static function numberOfSubscriptionsIsBelowOrEqualTo(CourseId $courseId, int $value): Constraint
    {
        return Constraint::create(
            'numberOfCourseSubscriptionsIsBelowLimit',
            self::numberOfSubscriptions($courseId),
            static fn (int $numberOfCourseSubscriptions) => $numberOfCourseSubscriptions <= $value
        );
    }

    /**
     * @return Projection<CourseTitle>
     */
    private static function title(CourseId $courseId): Projection
    {
        return AtomicProjection::create($courseId, initialState: CourseTitle::fromString(''))
            ->when(CourseDefined::class, static fn ($_, CourseDefined $event) => $event->courseTitle)
            ->when(CourseRenamed::class, static fn ($_, CourseRenamed $event) => $event->newCourseTitle)
        ;
    }

    public static function titleEquals(CourseId $courseId, CourseTitle $candidate): Constraint
    {
        return Constraint::create(
            'courseTitleEquals',
            self::title($courseId),
            static fn (CourseTitle $currentTitle) => $currentTitle->equals($candidate)
        );
    }
}
