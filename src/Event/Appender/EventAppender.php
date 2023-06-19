<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Event\Appender;

use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Exception\ConditionalAppendFailed;
use Wwwision\DCBEventStore\Model\DomainEvent;
use Wwwision\DCBEventStore\Model\DomainEvents;
use Wwwision\DCBEventStore\Model\Events;
use Wwwision\DCBEventStore\Model\ExpectedLastEventId;
use Wwwision\DCBEventStore\Model\StreamQuery;
use Wwwision\DCBExample\Event\Normalizer\EventNormalizer;

use function array_map;
use function iterator_to_array;

final readonly class EventAppender
{
    private EventNormalizer $eventNormalizer;

    public function __construct(
        private EventStore $eventStore,
        private StreamQuery $query,
        private ExpectedLastEventId $expectedLastEventId,
    ) {
        $this->eventNormalizer = new EventNormalizer();
    }

    /**
     * @throws ConditionalAppendFailed
     */
    public function append(DomainEvents|DomainEvent $domainEvents): void
    {
        $convertedEvents = Events::fromArray(array_map($this->eventNormalizer->convertDomainEvent(...), $domainEvents instanceof DomainEvents ? iterator_to_array($domainEvents) : [$domainEvents]));
        $this->eventStore->conditionalAppend($convertedEvents, $this->query, $this->expectedLastEventId);
    }
}
