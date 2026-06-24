<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\DefineCourse\Commands;

use Wwwision\DCBExample\Model\Course\Dto\CourseId;
use Wwwision\DCBExample\Model\Course\Dto\CourseTitle;

final readonly class RenameCourse
{
    public CourseId $courseId;
    public CourseTitle $newTitle;

    public function __construct(
        CourseId|string $courseId,
        CourseTitle|string $newTitle,
    ) {
        if (is_string($courseId)) {
            $courseId = CourseId::fromString($courseId);
        }
        if (is_string($newTitle)) {
            $newTitle = CourseTitle::fromString($newTitle);
        }
        $this->courseId = $courseId;
        $this->newTitle = $newTitle;
    }
}
