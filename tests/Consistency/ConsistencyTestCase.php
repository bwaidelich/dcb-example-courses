<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\TestCase;
use Wwwision\DCBTools\Testing\ConcurrencyTester;

/**
 * Domain base for the course consistency tests: builds the {@see ConcurrencyTester} collaborator wired to the course
 * serializer and defaults. Tests drive it directly as `$this->tester->given(...)->when(...)->then(...)`; each concrete
 * test wires its handler in `setUp()`.
 */
abstract class ConsistencyTestCase extends TestCase
{
    protected ConcurrencyTester $tester;

    protected function setUp(): void
    {
        $this->tester = ConcurrencyTester::inMemory(CourseTestSupport::serializer(), CourseTestSupport::defaults());
    }
}
