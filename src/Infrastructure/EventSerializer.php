<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Infrastructure;

use JsonException;
use RuntimeException;
use Webmozart\Assert\Assert;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\Event\Tags;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBExample\Infrastructure\ProvidesTags;

use function get_debug_type;
use function get_object_vars;
use function json_decode;
use function json_encode;
use function sprintf;
use function strrpos;
use function substr;

use const JSON_THROW_ON_ERROR;

/**
 * Simple converter that expects Domain Events to implement the {@see DomainEvent} interface
 */
final readonly class EventSerializer
{
    public function __construct(
        private string $eventClassNamespace,
    ) {
    }

    public function convertEvent(Event|SequencedEvent $event): DomainEvent
    {
        if ($event instanceof SequencedEvent) {
            $event = $event->event;
        }
        try {
            $payload = json_decode($event->data->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to decode JSON: %s', $e->getMessage()), 1684510536, $e);
        }
        Assert::isArray($payload);
        /** @var class-string<DomainEvent> $eventClassName */
        $eventClassName = $this->eventClassNamespace . '\\' . $event->type->value;
        return $eventClassName::fromArray($payload);
    }

    public function convertDomainEvent(DomainEvent $domainEvent): Event
    {
        try {
            $eventData = json_encode($domainEvent, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to JSON encode payload of domain event %s: %s', get_debug_type($domainEvent), $e->getMessage()), 1685965020, $e);
        }
        $tags = Tags::create();
        foreach (get_object_vars($domainEvent) as $value) {
            if ($value instanceof ProvidesTags) {
                $tags = $tags->merge($value->tags());
            }
        }
        return Event::create(
            type: substr($domainEvent::class, strrpos($domainEvent::class, '\\') + 1),
            data: $eventData,
            tags: $tags,
        );
    }
}
