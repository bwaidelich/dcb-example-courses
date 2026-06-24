<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Model\Course\Dto;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Traversable;
use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBEventStore\Event\Tags;
use Wwwision\DCBTools\Event\ProvidesTags;

use function array_filter;
use function array_map;

/**
 * A type-safe set of {@see CourseId} instances
 *
 * @implements IteratorAggregate<CourseId>
 */
final class CourseIds implements IteratorAggregate, Countable, ProvidesTags
{
    /**
     * @param CourseId[] $ids
     */
    private function __construct(
        private readonly array $ids,
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

    public function isEmpty(): bool
    {
        return $this->ids === [];
    }

    /**
     * @template T
     * @param Closure(CourseId): T $callback
     * @return array<T>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->ids);
    }

    public function tags(): Tags
    {
        $tags = Tags::create();
        foreach ($this->ids as $id) {
            $tags = $tags->merge($id->tags());
        }
        return $tags;
    }
}
