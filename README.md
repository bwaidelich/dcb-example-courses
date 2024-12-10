# Dynamic Consistency Boundary Example

Simple example for the Dynamic Consistency Boundary pattern [described by Sara Pellegrini](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html).

The purpose of this package is to explore the idea, find potential pitfalls and to spread the word.

**tl;dr** Have a look at the [example script](index.php) or [Behat Tests](tests/Behat) to see this in action.

## Background

Dynamic Consistency Boundary (aka DCB) allow to enforce hard constraints in Event-Sourced systems without having to rely on individual Event Streams.
This facilitates focussing on the _behavior_ of the Domain Model rather than on its rigid structure. It also allows for simpler architecture and potential
performance improvements as multiple projections can act on the same events without requiring synchronization.

Read all about this interesting approach in the blog post mentioned above or watch Saras talk on [YouTube](https://www.youtube.com/watch?v=DhhxKoOpJe0&t=150s) (Italian with English subtitles).
This package models the example of this presentation (with a few deviations) using the [wwwision/dcb-eventstore](https://github.com/bwaidelich/dcb-eventstore) package and the [wwwision/dcb-eventstore-doctrine](https://github.com/bwaidelich/dcb-eventstore-doctrine) database adapter.

### Important Classes / Concepts

* [Commands](src%2FCommand) are just a concept of this example package. They implement the [Command Marker Interface](src%2FCommand%2FCommand.php)
* The [CommandHandler](src/CommandHandler.php) is the central authority, handling and verifying incoming Command
* It uses in-memory [Projections](src%2FProjection%2FProjection.php) to enforce hard constraints
* For each command handler a [DecisionModel](src%2FDecisionModel%2FDecisionModel.php) instance is built that contains the state of those in-memory projections and the [AppendCondition](https://github.com/bwaidelich/dcb-eventstore/blob/main/Specification.md#AppendCondition) for new events
* The [EventSerializer](src%2FEventSerializer.php) can convert [DomainEvent](src%2FEvent%2DDomainEvent.php) instances to writable events, vice versa
* *Note:* This package contains no Read Model (i.e. classic projections) yet

### Considerations / Findings

I always had the feeling, that the focus on Event Streams is a distraction to Domain-driven design. So I was very happy to come across this concept.
In the meantime I have had the chance to test it in multiple real world scenarios, and it works really well for me and simplifies things (in spite of some minor caveats in the current implementation):

* It becomes trivial to enforce constraints involving multiple entities (like in this example).
* Global uniqueness (aka "the unique username problem") can easily be achieved with DCB
* Consecutive sequences (e.g. invoice number) can be done without reservation patterns and by only reading a single event per constraint check
* When using composition like in this example, phe in-memory projections are surprisingly small because they focus on a single responsibility
* ...and more

## Usage

Install via [composer](https://getcomposer.org):

```shell
composer create-project wwwision/dcb-example-courses
```

Now you should be able to run the [example script](index.php) via

```shell
php dcb-example-courses/index.php
```

And you should get ...no output at all. That's because the example script currently satisfy all constraints.
Try changing the script to test, that the business rules are actually enforced, for example you could add the line:

```php
$commandHandler->handle(SubscribeStudentToCourse::create(courseId: 'c1', studentId: 's2'));
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
composer test:behat
```

## Acknowledgment

Most of the implementation of these packages are based on the great groundwork done by [Sara Pellegrini](https://sara.event-thinking.io/), so all praise belong to her!

## Contributions

I'm really curious to get feedback on this one.
Feel free to start/join a [discussion](https://github.com/bwaidelich/dcb-example/discussions), [issues](https://github.com/bwaidelich/dcb-example/issues) or [Pull requests](https://github.com/bwaidelich/dcb-example/pulls).