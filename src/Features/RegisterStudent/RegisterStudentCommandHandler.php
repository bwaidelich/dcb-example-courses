<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Features\RegisterStudent;

use Wwwision\DCBExample\Features\RegisterStudent\Commands\RegisterStudent;
use Wwwision\DCBExample\Features\RegisterStudent\Events\StudentRegistered;
use Wwwision\DCBExample\Model\Student\StudentDecisionModels as Student;
use Wwwision\DCBTools\DomainEventAppender;

use function Wwwision\DCBTools\not;

final readonly class RegisterStudentCommandHandler
{
    public function __construct(
        private DomainEventAppender $eventStore,
    ) {
    }

    public function __invoke(RegisterStudent $command): void
    {
        $this->eventStore->append(
            constraints: [
                not(Student::isRegistered($command->studentId))
            ],
            onSuccess: fn () => new StudentRegistered($command->studentId),
        );
    }
}
