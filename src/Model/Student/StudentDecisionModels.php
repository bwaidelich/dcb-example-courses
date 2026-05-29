<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Student;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBTools\DecisionModel\Constraint;

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
            name: 'studentIsRegistered',
            projection: StudentProjections::idIsUsed($studentId),
            predicate: static fn (bool $state) => $state
        );
    }

    public static function isSubscribedToCourse(StudentId $studentId, CourseId $courseId): Constraint
    {
        return Constraint::create(
            name: 'studentSubscribedToCourse',
            projection: StudentProjections::subscriptions($studentId),
            predicate: static fn (CourseIds $courseIds) => $courseIds->contains($courseId),
        );
    }

    public static function numberOfSubscriptionsIsBelowLimit(StudentId $studentId): Constraint
    {
        return Constraint::create(
            name: 'numberOfStudentSubscriptionsIsBelowLimit',
            projection: StudentProjections::subscriptions($studentId),
            predicate: static fn (CourseIds $courseIds) => $courseIds->count() < self::MAX_SUBSCRIPTIONS_PER_STUDENT,
        );
    }
}
