<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Types\StudentId;

/**
 * Contract for student related Domain Events
 */
interface StudentEvent extends DomainEvent
{
    public StudentId $studentId { get; }
}
