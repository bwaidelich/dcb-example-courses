<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Event;

use Webmozart\Assert\Assert;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * Domain Event that occurs when a new student was registered in the system
 */
final readonly class StudentRegistered implements DomainEvent
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
