<?php

declare(strict_types=1);

namespace Wwwision\DCBExample;

use JsonException;
use RuntimeException;
use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Types\Event;
use Wwwision\DCBEventStore\Types\EventData;
use Wwwision\DCBLibrary\DomainEvent;
use Wwwision\DCBEventStore\Types\EventId;
use Wwwision\DCBEventStore\Types\EventMetadata;
use Wwwision\DCBEventStore\Types\EventType;

use function get_debug_type;
use function json_decode;
use function json_encode;
use function sprintf;
use function strrpos;
use function substr;

use function Wwwision\Types\instantiate;
use const JSON_THROW_ON_ERROR;

/**
 * Simple converter that expects Domain Events to implement the {@see DomainEvent} interface
 */
final readonly class EventSerializer implements \Wwwision\DCBLibrary\EventSerializer
{
    public function convertEvent(Event $event): DomainEvent
    {
        try {
            $payload = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to decode JSON: %s', $e->getMessage()), 1684510536, $e);
        }
        Assert::isArray($payload);
        /** @var class-string<DomainEvent> $eventClassName */
        $eventClassName = '\\Wwwision\\DCBExample\\Events\\' . $event->type->value;
        $domainEvent = instantiate($eventClassName, $payload);
        Assert::isInstanceOf($domainEvent, DomainEvent::class);
        return $domainEvent;
    }

    public function convertDomainEvent(DomainEvent $domainEvent): Event
    {
        try {
            $eventData = json_encode($domainEvent, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to JSON encode payload of domain event %s: %s', get_debug_type($domainEvent), $e->getMessage()), 1685965020, $e);
        }
        return new Event(
            EventId::create(),
            EventType::fromString(substr($domainEvent::class, strrpos($domainEvent::class, '\\') + 1)),
            EventData::fromString($eventData),
            $domainEvent->tags(),
            EventMetadata::none()
        );
    }
}
