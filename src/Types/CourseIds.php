<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\ListBased;
use function array_filter;
use function array_map;
use function Wwwision\Types\instantiate;

/**
 * @implements IteratorAggregate<CourseId>
 */
#[Description('A type-safe set of {@see CourseId} instances')]
#[ListBased(itemClassName: CourseId::class)]
final class CourseIds implements IteratorAggregate, Countable
{
    /**
     * @param CourseId[] $ids
     */
    private function __construct(
        public readonly array $ids,
    ) {
    }

    public static function create(CourseId ...$ids): self
    {
        return instantiate(self::class, $ids);
    }

    public static function none(): self
    {
        return instantiate(self::class, []);
    }

    public static function fromStrings(string ...$ids): self
    {
        return self::create(...array_map(static fn (string $type) => CourseId::fromString($type), $ids));
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
        return self::create(...[...$this->ids, $courseId]);
    }

    public function without(CourseId $courseId): self
    {
        if (!$this->contains($courseId)) {
            return $this;
        }
        return self::create(...array_filter($this->ids, static fn (CourseId $id) => !$id->equals($courseId)));
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
