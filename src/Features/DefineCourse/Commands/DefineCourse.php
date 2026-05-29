<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseCapacity;
use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;

final readonly class DefineCourse
{
    public CourseId $courseId;
    public CourseCapacity $initialCapacity;
    public CourseTitle $courseTitle;

    public function __construct(
        CourseId|string $courseId,
        CourseCapacity|int $initialCapacity,
        CourseTitle|string $courseTitle,
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
        $this->courseId = $courseId;
        $this->initialCapacity = $initialCapacity;
        $this->courseTitle = $courseTitle;
    }
}
