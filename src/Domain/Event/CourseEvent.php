<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Event;

use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * Contract for course related Domain Events
 */
interface CourseEvent extends DomainEvent
{
    public CourseId $courseId { get; }
}
