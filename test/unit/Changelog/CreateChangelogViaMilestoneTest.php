<?php

declare(strict_types=1);

namespace Laminas\AutomaticReleases\Test\Unit\Changelog;

use Laminas\AutomaticReleases\Changelog\CreateChangelogViaMilestone;
use Laminas\AutomaticReleases\Changelog\ReleaseChangelogEvent;
use Laminas\AutomaticReleases\Git\Value\BranchName;
use Laminas\AutomaticReleases\Git\Value\SemVerVersion;
use Laminas\AutomaticReleases\Github\Api\GraphQL\Query\GetMilestoneChangelog\Response\Milestone;
use Laminas\AutomaticReleases\Github\CreateReleaseText;
use Laminas\AutomaticReleases\Github\Value\RepositoryName;
use PHPUnit\Framework\TestCase;

use function assert;

final class CreateChangelogViaMilestoneTest extends TestCase
{
    private ReleaseChangelogEvent $event;

    private Milestone $milestone;

    private RepositoryName $repositoryName;

    private SemVerVersion $version;

    public function setUp(): void
    {
        $this->milestone = Milestone::fromPayload([
            'number'       => 1,
            'closed'       => false,
            'title'        => '2.0.0',
            'description'  => null,
            'issues'       => ['nodes' => []],
            'pullRequests' => ['nodes' => []],
            'url'          => 'https://github.com/example/not-a-real-repository/milestones/1',
        ]);

        $this->repositoryName = RepositoryName::fromFullName('example/not-a-real-repository');

        $this->version = SemVerVersion::fromMilestoneName('2.0.0');

        $this->event = new ReleaseChangelogEvent(
            $this->repositoryName,
            __DIR__,
            BranchName::fromName('2.0.x'),
            $this->milestone,
            $this->version
        );
    }

    public function testProxiesToComposedCreateReleaseTextInstanceWithValuesPulledFromEvent(): void
    {
        $expectedChangelog = 'The changelog';

        $generator = $this->createMock(CreateReleaseText::class);
        assert($generator instanceof CreateReleaseText);

        $generator
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->equalTo($this->milestone),
                $this->equalTo($this->repositoryName),
                $this->equalTo($this->version)
            )
            ->willReturn($expectedChangelog);

        $createChangelogViaMilestone = new CreateChangelogViaMilestone($generator);

        $this->assertSame($expectedChangelog, $createChangelogViaMilestone($this->event));
    }
}
