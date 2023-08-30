<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\ReadModel\Course;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Wwwision\Types\Attributes\ListBased;

/**
 * @implements IteratorAggregate<Course>
 */
#[ListBased(itemClassName: Course::class)]
final readonly class Courses implements IteratorAggregate
{
    /**
     * @param array<Course> $courses
     */
    private function __construct(
        private array $courses,
    ) {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->courses);
    }
}