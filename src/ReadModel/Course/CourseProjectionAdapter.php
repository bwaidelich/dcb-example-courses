<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\ReadModel\Course;

use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBLibrary\ProvidesReset;
use Wwwision\DCBLibrary\ProvidesSetup;

interface CourseProjectionAdapter extends ProvidesSetup, ProvidesReset
{

    public function saveCourse(Course $course): void;

    // -------- READ ----------

    public function courses(): Courses;

    public function courseById(CourseId $courseId): ?Course;
}