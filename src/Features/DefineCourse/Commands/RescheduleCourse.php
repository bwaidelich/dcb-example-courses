<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseSchedule;

final readonly class RescheduleCourse
{
    public CourseId $courseId;
    public CourseSchedule $newSchedule;

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
