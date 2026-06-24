<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Events;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseSchedule;
use Wwwision\DCBTools\Event\DomainEvent;

/**
 * Domain Event that occurs when a the schedule of a course was changed
 */
final readonly class CourseRescheduled implements DomainEvent
{
    public CourseId $courseId;
    public CourseSchedule $newSchedule;

    /**
     * @param CourseSchedule|array{start:string,end:string} $newSchedule
     */
    public function __construct(
        CourseId|string $courseId,
        CourseSchedule|array $newSchedule,
    ) {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_array($newSchedule)) {
            $newSchedule = CourseSchedule::fromArray($newSchedule);
        }
        $this->courseId = $courseId;
        $this->newSchedule = $newSchedule;
    }
}
