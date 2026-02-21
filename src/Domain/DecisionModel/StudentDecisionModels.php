<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\DecisionModel;

use Wwwision\DCBExample\Domain\Event\StudentRegistered;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Projection\StudentProjections;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseIds;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraint;
use Wwwision\DCBExample\Infrastructure\Projection\AtomicProjection;
use Wwwision\DCBExample\Infrastructure\Projection\Projection;

final readonly class StudentDecisionModels
{
    private const int MAX_SUBSCRIPTIONS_PER_STUDENT = 10;

    /**
     * This class only contains static members and is not meant to be initialized
     */
    private function __construct()
    {
    }

    public static function isRegistered(StudentId $studentId): Constraint
    {
        return Constraint::create(
            key: 'studentIsRegistered',
            wrappedProjection: StudentProjections::idIsUsed($studentId),
            transformer: static fn (bool $state) => $state
        );
    }

    public static function isSubscribedToCourse(StudentId $studentId, CourseId $courseId): Constraint
    {
        return Constraint::create(
            key: 'studentSubscribedToCourse',
            wrappedProjection: StudentProjections::subscriptions($studentId),
            transformer: static fn (CourseIds $courseIds) => $courseIds->contains($courseId),
        );
    }

    public static function numberOfSubscriptionsIsBelowLimit(StudentId $studentId): Constraint
    {
        return Constraint::create(
            key: 'numberOfStudentSubscriptionsIsBelowLimit',
            wrappedProjection: StudentProjections::subscriptions($studentId),
            transformer: static fn (CourseIds $courseIds) => $courseIds->count() < self::MAX_SUBSCRIPTIONS_PER_STUDENT,
        );
    }
}
