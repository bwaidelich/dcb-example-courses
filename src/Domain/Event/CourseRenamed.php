<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Domain\Event;

use Webmozart\Assert\Assert;
use Wwwision\DCBExample\Domain\Types\CourseId;
use Wwwision\DCBExample\Domain\Types\CourseTitle;

/**
 * Domain Event that occurs when the title of a course has changed
 */
final readonly class CourseRenamed implements CourseEvent
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
}
