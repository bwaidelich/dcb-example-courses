<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course\Dto;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function array_map;

/**
 * A type-safe set of {@see CourseSchedule} instances
 *
 * @implements IteratorAggregate<CourseSchedule>
 */
final class CourseSchedules implements IteratorAggregate
{
    /**
     * @var array<CourseSchedule>
     */
    private array $schedules;

    public function __construct(
        CourseSchedule ...$schedules,
    ) {
        $this->schedules = array_values($schedules);
    }

    public static function create(CourseSchedule ...$schedules): self
    {
        return new self(...$schedules);
    }

    public static function none(): self
    {
        return new self(...[]);
    }

    public static function fromArray(array $schedules): self
    {
        return self::create(...array_map(static fn (array|CourseSchedule $schedule) => is_array($schedule) ? CourseSchedule::fromArray($schedule) : $schedule, $schedules));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->schedules);
    }

    public function hasOverlaps(): bool
    {
        $sorted = $this->schedules;
        usort($sorted, static fn (CourseSchedule $a, CourseSchedule $b) => $a->start->value <=> $b->start->value);
        for ($i = 1, $count = count($sorted); $i < $count; $i++) {
            if ($sorted[$i]->start->value < $sorted[$i - 1]->end->value) {
                return true;
            }
        }
        return false;
    }
}
