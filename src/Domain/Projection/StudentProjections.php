<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Projection;

use Wwwision\DCBExample\Domain\Event\StudentRegistered;
use Wwwision\DCBExample\Domain\Event\StudentSubscribedToCourse;
use Wwwision\DCBExample\Domain\Event\StudentUnsubscribedFromCourse;
use Wwwision\DCBExample\Domain\Types\CourseIds;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\Projection\AtomicProjection;
use Wwwision\DCBExample\Infrastructure\Projection\Projection;

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
