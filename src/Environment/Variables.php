<?php

declare(strict_types=1);

namespace Doctrine\AutomaticReleases\Environment;

use Doctrine\AutomaticReleases\Gpg\SecretKeyId;

/** @psalm-immutable */
interface Variables
{
    /** @psalm-return non-empty-string */
    public function githubOrganisation() : string;

    /** @psalm-return non-empty-string */
    public function githubToken() : string;

    public function signingSecretKey() : SecretKeyId;

    /** @psalm-return non-empty-string */
    public function gitAuthorName() : string;

    /** @psalm-return non-empty-string */
    public function gitAuthorEmail() : string;

    /** @psalm-return non-empty-string */
    public function githubEventPath() : string;

    /** @psalm-return non-empty-string */
    public function githubWorkspacePath() : string;
}
