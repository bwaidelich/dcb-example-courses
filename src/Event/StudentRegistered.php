<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Webmozart\Assert\Assert;
use Wwwision\DCBExample\Types\StudentId;

/**
 * Domain Events that occurs when a new student was registered in the system
 */
final readonly class StudentRegistered implements StudentEvent
{
    public function __construct(
        public StudentId $studentId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'studentId');
        Assert::string($data['studentId']);
        return new self(
            StudentId::fromString($data['studentId']),
        );
    }
}
