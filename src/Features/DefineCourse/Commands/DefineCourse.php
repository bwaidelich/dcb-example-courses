<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseSchedule;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;

final readonly class DefineCourse
{
    public CourseId $courseId;
    public CourseCapacity $initialCapacity;
    public CourseTitle $courseTitle;
    public CourseSchedule $schedule;

    public function __construct(
        CourseId|string $courseId,
        CourseCapacity|int $initialCapacity,
        CourseTitle|string $courseTitle,
        CourseSchedule|array $schedule,
    ) {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_int($initialCapacity)) {
            $initialCapacity = CourseCapacity::fromInteger($initialCapacity);
        }
        if (is_string($courseTitle)) {
            $courseTitle = CourseTitle::fromString($courseTitle);
        }
        if (is_array($schedule)) {
            $schedule = CourseSchedule::fromArray($schedule);
        }
        $this->courseId = $courseId;
        $this->initialCapacity = $initialCapacity;
        $this->courseTitle = $courseTitle;
        $this->schedule = $schedule;
    }
}
