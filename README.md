# Dynamic Consistency Boundary Example

Simple example for the Dynamic Consistency Boundary pattern [described by Sara Pellegrini](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html).

The purpose of this package is to explore the idea, find potential pitfalls and to spread the word.

**tl;dr** Have a look at the [example script](index.php) or [Behat Tests](tests/Behat) to see this in action.

## Background

Dynamic Consistency Boundary (aka DCB) allow to enforce hard constraints in Event-Sourced systems without having to rely on individual Event Streams.
This facilitates focussing on the _behavior_ of the Domain Model rather than on its rigid structure. It also allows for simpler architecture and potential
performance improvements as multiple aggregates can act on the same events without requiring synchronization.

Read all about this interesting approach in the blog post mentioned above or watch Saras talk on [YouTube](https://www.youtube.com/watch?v=DhhxKoOpJe0&t=150s) (Italian with English subtitles).
This package models the example of this presentation (with a few deviations) using the [wwwision/dcb-eventstore](https://github.com/bwaidelich/dcb-eventstore) package and the [wwwision/dcb-eventstore-doctrine](https://raw.githubusercontent.com/bwaidelich/dcb-eventst) database adapter.

### Important Classes / Concepts

* [Commands](src/Command) are just a concept of this example package. They implement the [Command Marker Interface](src/Command/Command.php)
* The [CommandHandler](src/CommandHandler.php) is the central authority, handling and verifying incoming Commands
* ...it uses the [AggregateLoader](https://github.com/bwaidelich/dcb-eventstore/blob/main/src/Aggregate/AggregateLoader.php) to interact with all involved Aggregates¹
* The [Aggregates](src/Model/Aggregate) are surprisingly small because they focus on a single responsibility (e.g. instead of a "CourseAggregate" there are three aggregates [CourseExistenceAggregate](src/Model/Aggregate/CourseExistenceAggregate.php), [CourseTitleAggregate](src/Model/Aggregate/CourseTitleAggregate.php) and [CourseCapacityAggregate](src/Model/Aggregate/CourseCapacityAggregate))
* ...Aggregates record [Events](src/Event) that are serialized with the [EventNormalizer](src/Event/Normalizer/EventNormalizer.php)
* This package contains no Read Model (e.g. projections) yet

### Considerations / Findings

I always had the feeling, that the focus on Event Streams is a distraction to Domain-driven design. So I was very happy to come across this concept.
So far I didn't have the chance to test it in a real world scenario, but it makes a lot of sense to me and IMO this example shows, that the approach
really works out in practice (in spite of some minor caveats in the current implementation¹).

#### Some further thoughts

* The signature of the [EventStore::append()](https://github.com/bwaidelich/dcb-eventstore/blob/main/src/EventStore.php#L36) method, is still a bit cumbersome and implicit
  * A `$lastEventId` parameter of `null` has a special meaning (Maybe a union type would be better suited here)
  * Instead of working with Event *IDs* it might make sense to use the global "sequence number" instead (in this implementation I have to expose that anyways) as that's easier to work with and to debug (as it gives expected vs actual value a clear order)
* The [StreamQuery](https://github.com/bwaidelich/dcb-eventstore/blob/main/src/Model/StreamQuery.php) has too many states because Domain Ids and Event Types can either be:
  * a) `null` => fallback matching all events
  * b) A set of values, matching events with at least one overlap
  * c) "none" => an empty set matching no events (required for the initial event and for tests)
* I don't like that Aggregates have to specify the event types explicitly ([example](https://github.com/bwaidelich/dcb-example/blob/main/src/Model/Aggregate/StudentSubscriptionsAggregate.php#L73)) – This is a potential source of bugs
  * Maybe the "projection" logic can be reworked such that affected event types can be extracted from it
* A lot of complexity comes from having to reconstitute multiple Aggregates at once¹
  * Maybe it makes more sense to have nested Aggregates, i.e. the [StudentSubscriptionsAggregate](https://github.com/bwaidelich/dcb-example/blob/main/src/Model/Aggregate/StudentSubscriptionsAggregate.php) could create the depending aggregates ([CourseExistenceAggregate](https://github.com/bwaidelich/dcb-example/blob/main/src/Model/Aggregate/CourseExistenceAggregate.php), [StudentExistenceAggregate](https://github.com/bwaidelich/dcb-example/blob/main/src/Model/Aggregate/StudentExistenceAggregate.php) and [CourseCapacityAggregate](https://github.com/bwaidelich/dcb-example/blob/main/src/Model/Aggregate/CourseCapacityAggregate.php) and do the composition that the [AggregateLoader](https://github.com/bwaidelich/dcb-eventstore/blob/main/src/Aggregate/AggregateLoader.php) does currently...

## Usage

Install via [composer](https://getcomposer.org):

```shell
composer create-project wwwision/dcb-example
```

Now you should be able to run the [example script](index.php) via

```shell
php dcb-example/index.php
```

And you should get ...no output at all. That's because the example script currently satisfy all constraints.
Try changing the script to test, that the business rules are actually enforced, for example you could add the line:

```php
$commandHandler->handle(new SubscribeStudentToCourse(CourseId::fromString('c1'), StudentId::fromString('s2')));
```

to the end of the file, which should lead to the following exception:

```
Failed to subscribe student with id "s2" to course with id "c1" because a student with that id does not exist
```

Alternatively, you could have a look at the [Behat Tests](tests/Behat):

## Tests

This package comes with 16 Behat scenarios covering all business features.
You can run the tests via

```shell
composer test-behat
```

## Acknowledgment

Most of the implementation of these packages are based on the great groundwork done by [Sara Pellegrini](https://sara.event-thinking.io/), so all praise belong to her!

## Contributions

I'm really curious to get feedback on this one.
Feel free to start/join a [discussion](https://github.com/bwaidelich/dcb-example/discussions), [issues](https://github.com/bwaidelich/dcb-example/issues) or [Pull requests](https://github.com/bwaidelich/dcb-example/pulls).

-----

¹ The purpose of the [AggregateLoader](https://github.com/bwaidelich/dcb-eventstore/blob/main/src/Aggregate/AggregateLoader.php)
is to allow interaction with multiple Aggregates without having to fetch multiple Event Streams.
It is currently one of the weakest links in this implementation because it adds some hidden complexity – I hope, that I
can rework this at some point
