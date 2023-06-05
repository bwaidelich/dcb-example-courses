<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event;

use Wwwision\DCBExample\Event\Normalizer\FromArraySupport;
use Wwwision\DCBExample\Model\CourseId;
use Wwwision\DCBExample\Model\StudentId;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainIds;
use Webmozart\Assert\Assert;

/**
 * Domain Event that occurs when a student was subscribed to a course
 *
 * Note: This event affects two entities (course and student)!
 */
final readonly class StudentSubscribedToCourse implements DomainEvent, FromArraySupport
{
    public function __construct(
        public CourseId $courseId,
        public StudentId $studentId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'courseId');
        Assert::string($data['courseId']);
        Assert::keyExists($data, 'studentId');
        Assert::string($data['studentId']);
        return new self(
            CourseId::fromString($data['courseId']),
            StudentId::fromString($data['studentId']),
        );
    }

    public function domainIds(): DomainIds
    {
        return DomainIds::create($this->courseId, $this->studentId);
    }
}
