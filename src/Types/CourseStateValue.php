<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Types;

enum CourseStateValue
{
    case NON_EXISTING;
    case CREATED;
    case FULLY_BOOKED;
}
