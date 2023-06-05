<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Event\Normalizer\FromArraySupport;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\CourseTitle;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Webmozart\Assert\Assert;

/**
 * Domain Event that occurs when the title of a course has changed
 */
final readonly class CourseRenamed implements DomainEvent, FromArraySupport
{
    public function __construct(
        public CourseId $courseId,
        public CourseTitle $newCourseTitle,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'courseId');
        Assert::string($data['courseId']);
        Assert::keyExists($data, 'newCourseTitle');
        Assert::string($data['newCourseTitle']);
        return new self(
            CourseId::fromString($data['courseId']),
            CourseTitle::fromString($data['newCourseTitle']),
        );
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId);
    }
}
