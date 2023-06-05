<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Event\Normalizer\FromArraySupport;
use Wwwision\DCBExample\Model\CourseCapacity;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Webmozart\Assert\Assert;

/**
 * Domain Event that occurs when a new course was created
 */
final readonly class CourseCreated implements DomainEvent, FromArraySupport
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

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }
}
