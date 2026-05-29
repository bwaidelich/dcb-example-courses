<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course;

use stdClass;
use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;
use Wwwision\DCBTools\DecisionModel\Constraint;
use Wwwision\DCBTools\Projection\CompositeProjection;

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
            name: 'courseExists',
            projection: CourseProjections::idIsUsed($courseId),
            predicate: static fn (bool $state) => $state
        );
    }

    public static function capacityEquals(CourseId $courseId, CourseCapacity $candidate): Constraint
    {
        return Constraint::create(
            name: 'courseCapacityEquals',
            projection: CourseProjections::capacity($courseId),
            predicate: static fn (CourseCapacity $currentCapacity) => $currentCapacity->equals($candidate)
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
            name: 'courseHasCapacity',
            projection: $projection,
            predicate: static fn (object $state) => $state->courseCapacity->value > $state->numberOfCourseSubscriptions
        );
    }

    public static function numberOfSubscriptionsIsBelowCapacity(CourseId $courseId, int $capacity): Constraint
    {
        return Constraint::create(
            name: 'numberOfCourseSubscriptionsIsBelowCapacity',
            projection: CourseProjections::numberOfSubscriptions($courseId),
            predicate: static fn (int $numberOfCourseSubscriptions) => $numberOfCourseSubscriptions <= $capacity
        );
    }

    public static function titleEquals(CourseId $courseId, CourseTitle $candidate): Constraint
    {
        return Constraint::create(
            name: 'courseTitleEquals',
            projection: CourseProjections::title($courseId),
            predicate: static fn (CourseTitle $currentTitle) => $currentTitle->equals($candidate)
        );
    }
}
