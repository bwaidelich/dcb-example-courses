<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseCapacity;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;

/**
 * Domain Events that occurs when a new course was created
 */
final readonly class CourseCreated implements DomainEvent
{
    public function __construct(
        public CourseId $courseId,
        public CourseCapacity $initialCapacity,
        public CourseTitle $courseTitle,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'courseId');
        Assert::string($data['courseId']);
        Assert::keyExists($data, 'initialCapacity');
        Assert::numeric($data['initialCapacity']);
        Assert::keyExists($data, 'courseTitle');
        Assert::string($data['courseTitle']);
        return new self(
            CourseId::fromString($data['courseId']),
            CourseCapacity::fromInteger((int)$data['initialCapacity']),
            CourseTitle::fromString($data['courseTitle']),
        );
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag());
    }
}
