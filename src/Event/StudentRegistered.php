<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Event\Normalizer\FromArraySupport;
use Wwwision\DCBExample\Model\StudentId;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Webmozart\Assert\Assert;

/**
 * Domain Event that occurs when a new student was registered in the system
 */
final readonly class StudentRegistered implements DomainEvent, FromArraySupport
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

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->studentId);
    }
}
