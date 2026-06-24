<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\Consistency;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Wwwision\DCBExample\Features\CourseSubscription\SubscribeStudentToCourseCommandHandler;
use Wwwision\DCBExample\Features\CourseSubscription\UnsubscribeStudentFromCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\ChangeCourseCapacityCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\Commands\ChangeCourseCapacity;
use Wwwision\DCBExample\Features\DefineCourse\Commands\RenameCourse;
use Wwwision\DCBExample\Features\DefineCourse\DefineCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RenameCourseCommandHandler;
use Wwwision\DCBExample\Features\DefineCourse\RescheduleCourseCommandHandler;
use Wwwision\DCBExample\Features\RegisterStudent\RegisterStudentCommandHandler;
use Wwwision\DCBTools\Testing\Architecture\ArchitectureAnalyzer;
use Wwwision\DCBTools\Testing\Architecture\ArchitectureReport;
use Wwwision\DCBTools\Testing\Architecture\HandlerScenario;
use Wwwision\DCBTools\Testing\Architecture\SelfRaceStatus;

/**
 * Architecture-level quality gates built from the same sample-command scans as {@see HandlerBoundariesTest}: boundary
 * breadth per handler, module coupling (could a feature be split into its own bounded context?), contention fan-in, and
 * the self-race probe (does every handler conflict with a concurrent duplicate of itself?).
 *
 * `RenameCourse` and `ChangeCourseCapacity` need a {@see HandlerScenario} command override: their defaults-built
 * commands carry the same title/capacity the warm-up `CourseDefined` already has, so the `not…Equals` constraint rejects
 * them and the probes would be inconclusive.
 */
#[CoversNothing]
final class ArchitectureTest extends TestCase
{
    private const array HANDLERS = [
        RegisterStudentCommandHandler::class,
        DefineCourseCommandHandler::class,
        RenameCourseCommandHandler::class,
        ChangeCourseCapacityCommandHandler::class,
        SubscribeStudentToCourseCommandHandler::class,
        UnsubscribeStudentFromCourseCommandHandler::class,
        RescheduleCourseCommandHandler::class,
    ];

    private function analyze(): ArchitectureReport
    {
        return ArchitectureAnalyzer::create(CourseTestSupport::serializer(), CourseTestSupport::defaults())->analyze(
            self::HANDLERS,
            scenarios: [
                RenameCourseCommandHandler::class => HandlerScenario::create(command: new RenameCourse('c1', 'Another Title')),
                ChangeCourseCapacityCommandHandler::class => HandlerScenario::create(command: new ChangeCourseCapacity('c1', 5)),
            ],
        );
    }

    public function testBoundaryBreadthStaysWithinLimits(): void
    {
        // Current high-water mark: SubscribeStudentToCourse reads 6 event types across courses and students (via a
        // 2-pass projection chain) – the most entangled decision of the model. Tighten if a refactoring shrinks it.
        $this->analyze()->assertBoundaryBreadthAtMost(maxEventTypes: 6, maxEntityTypes: 2, maxReadPasses: 3);
    }

    public function testAllFeaturesFormASingleBoundedContext(): void
    {
        // CourseSubscription reads DefineCourse's and RegisterStudent's events, so none of the three features could be
        // split off without importing the others' events – the model is (deliberately) one bounded context.
        $coupling = $this->analyze()->coupling();

        self::assertSame([['CourseSubscription', 'DefineCourse', 'RegisterStudent']], $coupling->clusters);

        $seams = array_map(static fn($shared) => $shared->eventType, $coupling->sharedEventTypes);
        self::assertContains('CourseDefined', $seams);
        self::assertContains('StudentRegistered', $seams);
    }

    public function testEveryHandlerConflictsWithAConcurrentDuplicateOfItself(): void
    {
        $report = $this->analyze();

        self::assertSame([], $report->withSelfRaceStatus(SelfRaceStatus::Inconclusive));
        $report->assertNoneVulnerable();
    }

    public function testContentionFanInStaysWithinLimits(): void
    {
        // Current high-water mark: SubscribeStudentToCourse can be conflicted by 5 of the 6 other handlers – the
        // contention hotspot of the model (its decision model touches courses, students and subscriptions).
        $this->analyze()->contention()->assertFanInAtMost(5);
    }
}
