<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversClass;
use Wwwision\DCBExample\Features\RegisterStudent\Commands\RegisterStudent;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;
use Wwwision\DCBExample\Features\RegisterStudent\RegisterStudentCommandHandler;

#[CoversClass(RegisterStudentCommandHandler::class)]
final class RegisterStudentConsistencyTest extends ConsistencyTestCase
{
    private RegisterStudentCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new RegisterStudentCommandHandler($this->tester->appender());
    }

    public function testRegisteringNewStudentIsAccepted(): void
    {
        $this->tester->when(fn() => ($this->handler)($this->tester->build(RegisterStudent::class)))
            ->then($this->tester->build(StudentRegistered::class))
            ->thenBoundaryIsBounded();
    }

    public function testRegisteringAnAlreadyRegisteredStudentIsRejected(): void
    {
        $this->tester->given($this->tester->build(StudentRegistered::class))
            ->when(fn() => ($this->handler)($this->tester->build(RegisterStudent::class)))
            ->thenFails('notStudentIsRegistered');
    }

    public function testConcurrentRegistrationOfTheSameStudentConflicts(): void
    {
        // Safety: a competing registration of the same student must reject this one.
        $this->tester->race($this->tester->build(StudentRegistered::class))
            ->when(fn() => ($this->handler)($this->tester->build(RegisterStudent::class)))
            ->thenConflicts();
    }

    public function testConcurrentRegistrationOfADifferentStudentIsAccepted(): void
    {
        // No false contention: the boundary is scoped to student:s1, so registering s2 concurrently is irrelevant.
        $this->tester->race($this->tester->build(StudentRegistered::class, studentId: 's2'))
            ->when(fn() => ($this->handler)($this->tester->build(RegisterStudent::class)))
            ->then($this->tester->build(StudentRegistered::class));
    }
}
