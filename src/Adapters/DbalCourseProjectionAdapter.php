<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Adapters;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Webmozart\Assert\Assert;
use Wwwision\DCBExample\ReadModel\Course\Course;
use Wwwision\DCBExample\ReadModel\Course\CourseProjectionAdapter;
use Wwwision\DCBExample\ReadModel\Course\Courses;
use Wwwision\DCBExample\Types\CourseId;
use Wwwision\DCBExample\Types\CourseTitle;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\StringSchema;
use function Wwwision\Types\instantiate;

final readonly class DbalCourseProjectionAdapter implements CourseProjectionAdapter
{

    private const TABLE_NAME = 'dcv_example_courses_p_courses';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function saveCourse(Course $course): void
    {
        $data = self::courseToDatabaseRow($course);
        $assignments = [];
        $parameters = [];
        foreach ($data as $columnName => $value) {
            $assignments[$columnName] = $this->connection->quoteIdentifier($columnName) . ' = :' . $columnName;
            $parameters[$columnName] = $value;
        }
        $sql = 'INSERT INTO ' . self::TABLE_NAME . ' SET ' . (implode(', ', $assignments)) . ' ON DUPLICATE KEY UPDATE ' . (implode(', ', $assignments));
        $this->connection->executeStatement($sql, $parameters);
    }

    public function courses(): Courses
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM ' . self::TABLE_NAME);
        $instances = array_map(self::courseFromDatabaseRow(...), $rows);
        return instantiate(Courses::class, $instances);
    }

    public function courseById(CourseId $courseId): ?Course
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = :courseId', ['courseId' => $courseId->value]);
        if ($row === false) {
            return null;
        }
        return self::courseFromDatabaseRow($row);
    }

    // -------- HELPERS, INFRASTRUCTURE ----------

    public function setup(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $schemaDiff = (new Comparator())->compareSchemas($schemaManager->introspectSchema(), self::databaseSchema());
        foreach ($schemaDiff->toSaveSql($this->connection->getDatabasePlatform()) as $statement) {
            $this->connection->executeStatement($statement);
        }
    }

    public function reset(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE ' . self::TABLE_NAME);
    }

    private static function databaseSchema(): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable(self::TABLE_NAME);

        $table->addColumn('id', Types::STRING, ['length' => self::maxLength(CourseId::class)]);
        $table->addColumn('title', Types::STRING, ['length' => self::maxLength(CourseTitle::class), 'notnull' => false]);
        $table->addColumn('state', Types::JSON);
        $table->setPrimaryKey(['id']);

        return $schema;
    }

    /**
     * @param class-string $className
     */
    private static function maxLength(string $className): int
    {
        $schema = Parser::getSchema($className);
        Assert::isInstanceOf($schema, StringSchema::class, sprintf('Failed to determine max length for class %s: Expected an instance of %%2$s. Got: %%s', $className));
        Assert::notNull($schema->maxLength, sprintf('Failed to determine max length for class %s: No maxLength constraint defined', $className));
        return $schema->maxLength;
    }

    /**
     * @param array<mixed> $row
     */
    private static function courseFromDatabaseRow(array $row): Course
    {
        return instantiate(Course::class, [
            'id' => $row['id'],
            'title' => $row['title'],
            'state' => json_decode($row['state'], true, 512, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array<mixed>
     */
    private static function courseToDatabaseRow(Course $course): array
    {
        return [
            'id' => $course->id->value,
            'title' => $course->title->value,
            'state' => json_encode($course->state, JSON_THROW_ON_ERROR),
        ];
    }
}
