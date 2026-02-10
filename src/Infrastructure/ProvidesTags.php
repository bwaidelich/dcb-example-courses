<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure;

use Wwwision\DCBEventStore\Event\Tag;
use Wwwision\DCBEventStore\Event\Tags;

interface ProvidesTags
{
    public function tags(): Tag|Tags;
}
