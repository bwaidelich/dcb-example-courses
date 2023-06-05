# Dynamic Consistency Boundary Example 

Simple example for the Dynamic Consistency Boundary pattern [described by Sara Pellegrini](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html).

The purpose of this package is to explore the idea, find potential pitfalls and to spread the word.
This package models the example from Saras presentation (with a few deviations) using the [wwwision/dcb-eventstore](https://github.com/bwaidelich/dcb-eventstore) package and the [wwwision/dcb-eventstore-doctrine](https://raw.githubusercontent.com/bwaidelich/dcb-eventstore-doctrine/main/composer.json) database adapter.

## Usage

Install via [composer](https://getcomposer.org):

```shell
composer create-project wwwision/dcb-example
```

> **Note**
> The example requires [PHP 8.2+](https://www.php.net/) to be installed.
> If composer complaints even though the right version is installed, try using the `--ignore-platform-reqs` flag

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