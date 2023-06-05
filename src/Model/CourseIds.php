<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_filter;
use function array_map;

/**
 * A type-safe set of {@see CourseId} instances
 *
 * @implements IteratorAggregate<CourseId>
 */
final class CourseIds implements IteratorAggregate, Countable
{
    /**
     * @param CourseId[] $ids
     */
    private function __construct(
        public readonly array $ids,
    ) {
        //Assert::notEmpty($this->ids, 'CourseIds must not be empty');
    }

    public static function create(CourseId ...$ids): self
    {
        return new self($ids);
    }

    public static function none(): self
    {
        return new self([]);
    }

    public static function fromStrings(string ...$ids): self
    {
        return new self(array_map(static fn (string $type) => CourseId::fromString($type), $ids));
    }

    public function contains(CourseId $id): bool
    {
        foreach ($this->ids as $existingId) {
            if ($existingId->equals($id)) {
                return true;
            }
        }
        return false;
    }

    public function with(CourseId $courseId): self
    {
        if ($this->contains($courseId)) {
            return $this;
        }
        return new self([...$this->ids, $courseId]);
    }

    public function without(CourseId $courseId): self
    {
        if (!$this->contains($courseId)) {
            return $this;
        }
        return new self(array_filter($this->ids, static fn (CourseId $id) => !$id->equals($courseId)));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->ids);
    }

    public function count(): int
    {
        return count($this->ids);
    }
}
