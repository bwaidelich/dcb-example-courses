<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Events;

use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Types\Tags;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\StudentId;

/**
 * Domain Events that occurs when a student was unsubscribed from a course
 *
 * Note: This event affects two entities (course and student)!
 */
final readonly class StudentUnsubscribedFromCourse implements DomainEvent
{
    public function __construct(
        public StudentId $studentId,
        public CourseId $courseId,
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
        return new self(StudentId::fromString($data['studentId']), CourseId::fromString($data['courseId']),);
    }

    public function tags(): Tags
    {
        return Tags::create($this->courseId->toTag(), $this->studentId->toTag());
    }
}
