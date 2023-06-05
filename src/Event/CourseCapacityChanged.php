<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBExample\Event\Normalizer\FromArraySupport;
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBEventStore\Model\DomainIds;
use Webmozart\Assert\Assert;

/**
 * Domain Event that occurs when the total capacity of a course has changed
 */
final readonly class CourseCapacityChanged implements DomainEvent, FromArraySupport
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $newCapacity,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'courseId');
        Assert::string($data['courseId']);
        Assert::keyExists($data, 'newCapacity');
        Assert::numeric($data['newCapacity']);
        return new self(
            CourseId::fromString($data['courseId']),
            CourseCapacity::fromInteger((int)$data['newCapacity']),
        );
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }
}
