<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Event;

use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * Contract for student related Domain Events
 */
interface StudentEvent extends DomainEvent
{
    public StudentId $studentId { get; }
}
