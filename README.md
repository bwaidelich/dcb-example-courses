# Dynamic Consistency Boundary Example

Simple example for the [Dynamic Consistency Boundary pattern](https://dcb.events).

The purpose of this package is to explore the idea, find potential pitfalls and to spread the word.

**tl;dr** Have a look at the [example script](index.php) or [Behat Tests](tests/Behat) to see this in action.

## Background

Dynamic Consistency Boundary (aka DCB) allow to enforce hard constraints in Event-Sourced systems without having to rely on individual Event Streams.
This facilitates focussing on the _behavior_ of the Domain Model rather than on its rigid structure. It also allows for simpler architecture and potential
performance improvements as multiple projections can act on the same events without requiring synchronization.

Read all about this interesting approach at https://dcb.events.

This package models the example of this presentation (with a few deviations) introducing some higher level concepts like "Decision Models" and "Atomic Projections" (see below) and using the [wwwision/dcb-eventstore](https://github.com/bwaidelich/dcb-eventstore) package and the [wwwision/dcb-eventstore-doctrine](https://github.com/bwaidelich/dcb-eventstore-doctrine) database adapter for persistence.

### Important Classes / Concepts

* The [App](src/Domain/App.php) is the central authority, handling and verifying incoming Commands (Note: Commands are simple method calls right now, but obviously command classes could be used as well)
* It uses in-memory [DecisionModels](src/Domain/DecisionModel) to enforce hard constraints via in-memory [Projections](src/Domain/Projection)
* The [EventSerializer](src/Infrastructure/EventSerializer.php) can convert [DomainEvent](src/Infrastructure/DomainEvent.php) instances to [Sequenced Events](https://dcb.events/specification/#sequenced-event), vice versa
* *Note:* This package contains no Read Model (i.e. classic persisted projections) yet

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
$app->subscribeStudentToCourse(StudentId::fromString('s2'), CourseId::fromString('c1'));
```

to the end of the file, which should lead to the following exception:

```
Constraint "studentIsRegistered" failed
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