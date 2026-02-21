<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use Wwwision\DCBExample\Infrastructure\DecisionModel\Constraint;

function not(Constraint $constraint): Constraint
{
    return Constraint::create(
        'not' . ucfirst($constraint->key),
        $constraint->projection,
        static fn ($state) => !$constraint->evaluate($state),
    );
}
