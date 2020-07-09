<?php

declare(strict_types=1);

namespace Doctrine\AutomaticReleases\Test\Unit\Git;

use Doctrine\AutomaticReleases\Environment\EnvironmentVariables;
use Doctrine\AutomaticReleases\Git\CreateTagViaConsole;
use Doctrine\AutomaticReleases\Git\FetchAndSetCurrentUserByReplacingCurrentOriginRemote;
use Doctrine\AutomaticReleases\Git\Value\BranchName;
use Doctrine\AutomaticReleases\Gpg\ImportGpgKeyFromStringViaTemporaryFile;
use Doctrine\AutomaticReleases\Gpg\SecretKeyId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Process\Process;
use function file_get_contents;

/** @covers \Doctrine\AutomaticReleases\Git\CreateTagViaConsole */
final class CreateTagViaConsoleTest extends TestCase
{
    private string $repository;
    /** @var EnvironmentVariables&MockObject */
    private EnvironmentVariables $variables;
    private SecretKeyId $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = (new ImportGpgKeyFromStringViaTemporaryFile())
            ->__invoke(file_get_contents(__DIR__ . '/../../asset/dummy-gpg-key.asc'));

        $this->variables  = $this->createMock(EnvironmentVariables::class);
        $this->repository = tempnam(sys_get_temp_dir(), 'CreateTagViaConsoleRepository');

        unlink($this->repository);
        mkdir($this->repository);

        // @TODO check if we need to set the git author and email here (will likely fail in CI)
        (new Process(['git', 'init'], $this->repository))
            ->mustRun();
        (new Process(['git', 'checkout', '-b', 'tag-branch'], $this->repository))
            ->mustRun();
        (new Process(['git', 'commit', '--allow-empty', '-m', 'a commit'], $this->repository))
            ->mustRun();
        (new Process(['git', 'checkout', '-b', 'ignored-branch'], $this->repository))
            ->mustRun();
        (new Process(['git', 'commit', '--allow-empty', '-m', 'another commit'], $this->repository))
            ->mustRun();
    }

    public function testCreatesSignedTag(): void
    {
        $sourceUri = $this->createMock(UriInterface::class);

        $sourceUri->method('__toString')
            ->willReturn($this->repository);

        (new CreateTagViaConsole())
            ->__invoke(
                $this->repository,
                BranchName::fromName('tag-branch'),
                'name-of-the-tag',
                'changelog text for the tag',
                $this->key
            );

        (new Process(['git', 'tag', '-v', 'name-of-the-tag'], $this->repository))
            ->mustRun();

        $fetchedTag = (new Process(['git', 'show', 'name-of-the-tag'], $this->repository))
            ->mustRun()
            ->getOutput();

        self::assertStringContainsString('tag name-of-the-tag', $fetchedTag);
        self::assertStringContainsString('changelog text for the tag', $fetchedTag);
        self::assertStringContainsString('a commit', $fetchedTag);
        self::assertStringContainsString('-----BEGIN PGP SIGNATURE-----', $fetchedTag);
    }
}
