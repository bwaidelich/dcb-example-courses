<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Exception;

use InvalidArgumentException;

/**
 * An exception that is thrown when the hard constraint checks of an aggregate are not satisfied at write time
 */
final class ConstraintException extends InvalidArgumentException
{
}
