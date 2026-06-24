<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Student;

use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentSubscribedToCourse;
use Wwwision\DCBExample\Features\CourseSubscription\Events\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;
use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Student\Dto\StudentId;
use Wwwision\DCBTools\Projection\AtomicProjection;
use Wwwision\DCBTools\Projection\Projection;

final readonly class StudentProjections
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
    public static function idIsUsed(StudentId $studentId): Projection
    {
        return AtomicProjection::create($studentId, initialState: false)
            ->when(StudentRegistered::class, true)
            ;
    }

    /**
     * @return Projection<CourseIds>
     */
    public static function subscriptions(StudentId $studentId): Projection
    {
        return AtomicProjection::create($studentId, initialState: CourseIds::none())
            ->when(StudentSubscribedToCourse::class, static fn (CourseIds $state, StudentSubscribedToCourse $event) => $state->with($event->courseId))
            ->when(StudentUnsubscribedFromCourse::class, static fn (CourseIds $state, StudentUnsubscribedFromCourse $event) => $state->without($event->courseId))
            ;
    }
}
