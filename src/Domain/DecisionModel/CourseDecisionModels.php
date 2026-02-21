<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\DecisionModel;

use stdClass;
use Wwwision\DCBExample\Domain\Event\CourseCapacityChanged;
use Wwwision\DCBExample\Domain\Event\CourseDefined;
use Wwwision\DCBExample\Domain\Event\CourseRenamed;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Projection\CourseProjections;
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
        return Constraint::create(
            key: 'courseExists',
            wrappedProjection: CourseProjections::idIsUsed($courseId),
            transformer: static fn (bool $state) => $state
        );
    }

    public static function capacityEquals(CourseId $courseId, CourseCapacity $candidate): Constraint
    {
        return Constraint::create(
            key: 'courseCapacityEquals',
            wrappedProjection: CourseProjections::capacity($courseId),
            transformer: static fn (CourseCapacity $currentCapacity) => $currentCapacity->equals($candidate)
        );
    }

    public static function hasFreeSeats(CourseId $courseId): Constraint
    {
        /** @var CompositeProjection<object{courseCapacity: CourseCapacity, numberOfCourseSubscriptions: int}> $projection */
        $projection = CompositeProjection::create([
            'courseCapacity' => CourseProjections::capacity($courseId),
            'numberOfCourseSubscriptions' => CourseProjections::numberOfSubscriptions($courseId),
        ], stdClass::class);
        return Constraint::create(
            key: 'courseHasCapacity',
            wrappedProjection: $projection,
            transformer: static fn (object $state) => $state->courseCapacity->value > $state->numberOfCourseSubscriptions
        );
    }

    public static function numberOfSubscriptionsIsBelowCapacity(CourseId $courseId, int $capacity): Constraint
    {
        return Constraint::create(
            key: 'numberOfCourseSubscriptionsIsBelowCapacity',
            wrappedProjection: CourseProjections::numberOfSubscriptions($courseId),
            transformer: static fn (int $numberOfCourseSubscriptions) => $numberOfCourseSubscriptions <= $capacity
        );
    }

    public static function titleEquals(CourseId $courseId, CourseTitle $candidate): Constraint
    {
        return Constraint::create(
            key: 'courseTitleEquals',
            wrappedProjection: CourseProjections::title($courseId),
            transformer: static fn (CourseTitle $currentTitle) => $currentTitle->equals($candidate)
        );
    }
}
