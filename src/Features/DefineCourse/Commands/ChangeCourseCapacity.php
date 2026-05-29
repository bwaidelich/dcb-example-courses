<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;

final readonly class ChangeCourseCapacity
{
    public CourseId $courseId;
    public CourseCapacity $newCapacity;

    public function __construct(
        CourseId|string $courseId,
        CourseCapacity|int $newCapacity,
    ) {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_int($newCapacity)) {
            $newCapacity = CourseCapacity::fromInteger($newCapacity);
        }
        $this->courseId = $courseId;
        $this->newCapacity = $newCapacity;
    }
}
