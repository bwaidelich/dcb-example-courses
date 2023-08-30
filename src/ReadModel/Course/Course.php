<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\ReadModel\Course;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseState;
use Wwwision\DCBExample\Types\CourseTitle;

final readonly class Course
{
    public function __construct(
        public CourseId $id,
        public CourseTitle $title,
        public CourseState $state,
    ) {
    }

    public function withTitle(CourseTitle $newTitle): self
    {
        return new self($this->id, $newTitle, $this->state);
    }

    public function withState(CourseState $newState): self
    {
        return new self($this->id, $this->title, $newState);
    }
}