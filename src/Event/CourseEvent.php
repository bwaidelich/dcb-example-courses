<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Types\CourseId;

/**
 * Contract for course related Domain Events
 */
interface CourseEvent extends DomainEvent
{
    public CourseId $courseId { get; }
}
