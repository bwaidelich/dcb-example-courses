<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\DecisionModel;

use Wwwision\DCBExample\Domain\Event\StudentRegistered;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseIds;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraint;
use Wwwision\DCBExample\Infrastructure\Projection\AtomicProjection;
use Wwwision\DCBExample\Infrastructure\Projection\Projection;

final readonly class StudentDecisionModels
{
    /**
     * This class only contains static members and is not meant to be initialized
     */
    private function __construct()
    {
    }

    public static function isRegistered(StudentId $studentId): Constraint
    {
        $projection = AtomicProjection::create($studentId, initialState: false)
            ->when(StudentRegistered::class, fn() => true)
        ;
        return Constraint::create(
            'studentIsRegistered',
            $projection,
            static fn (bool $state) => $state
        );
    }

    /**
     * @return Projection<CourseIds>
     */
    private static function subscriptions(StudentId $studentId): Projection
    {
        return AtomicProjection::create($studentId, initialState: CourseIds::none())
            ->when(StudentSubscribedToCourse::class, static fn (CourseIds $state, StudentSubscribedToCourse $event) => $state->with($event->courseId))
            ->when(StudentUnsubscribedFromCourse::class, static fn (CourseIds $state, StudentUnsubscribedFromCourse $event) => $state->without($event->courseId))
        ;
    }

    public static function isSubscribedToCourse(StudentId $studentId, CourseId $courseId): Constraint
    {
        return Constraint::create(
            'studentSubscribedToCourse',
            self::subscriptions($studentId),
            static fn (CourseIds $courseIds) => $courseIds->contains($courseId),
        );
    }

    public static function numberOfSubscriptionsIsBelow(StudentId $studentId, int $value): Constraint
    {
        return Constraint::create(
            'numberOfStudentSubscriptionsIsBelowLimit',
            self::subscriptions($studentId),
            static fn (CourseIds $courseIds) => $courseIds->count() < $value
        );
    }
}
