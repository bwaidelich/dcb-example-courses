<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Student\Dto;

use Wwwision\DCBExample\Model\Course\Dto\CourseIds;
use Wwwision\DCBExample\Model\Student\StudentProjections;
use Wwwision\DCBTools\DecisionModel\ProjectedProperty;

final readonly class StudentSubscriptions
{
    public function __construct(
        #[ProjectedProperty(StudentProjections::subscriptions(...))]
        public CourseIds $courseIds,
    ) {
    }
}
