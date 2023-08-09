<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;

/**
 * Domain Events that occurs when the total capacity of a course has changed
 */
final readonly class CourseCapacityChanged implements DomainEvent
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

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag());
    }
}
