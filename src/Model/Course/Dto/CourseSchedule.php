<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course\Dto;

use JsonSerializable;
use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBTools\Event\ProvidesTags;

/**
 * Start and end date and time of a course
 */
final readonly class CourseSchedule
{
    public function __construct(
        public DateAndTime $start,
        public DateAndTime $end,
    ) {
    }

    /**
     * @param array<mixed> $schedule
     */
    public static function fromArray(array $schedule): self
    {
        Assert::isMap($schedule);
        Assert::keyExists($schedule, 'start');
        Assert::string($schedule['start']);
        Assert::keyExists($schedule, 'end');
        Assert::string($schedule['end']);
        return new self(
            DateAndTime::fromString($schedule['start']),
            DateAndTime::fromString($schedule['end']),
        );
    }

    public function overlaps(self $other): bool
    {
        return $this->start < $other->end
            && $other->start < $this->end;
    }
}
