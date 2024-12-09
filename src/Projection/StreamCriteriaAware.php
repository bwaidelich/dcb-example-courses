<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Projection;

use Wwwision\DCBEventStore\Types\StreamQuery\Criteria;

interface StreamCriteriaAware
{
    public function getCriteria(): Criteria;
}
