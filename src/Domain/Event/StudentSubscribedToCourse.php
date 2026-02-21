<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Event;

use Webmozart\Assert\Assert;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\StudentId;
use Wwwision\DCBExample\Infrastructure\DomainEvent;

/**
 * Domain Event that occurs when a student was subscribed to a course
 *
 * Note: This event affects two entities (course and student)!
 */
final readonly class StudentSubscribedToCourse implements DomainEvent
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
}
