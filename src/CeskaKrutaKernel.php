<?php

declare(strict_types=1);

namespace CeskaKruta\Web;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class CeskaKrutaKernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Production is FTP-only hosting: PHP runs as a different user than FTP, so the FTP user cannot delete
     * files PHP wrote into var/cache. Every deploy therefore gets its own cache dir (SENTRY_RELEASE = commit,
     * written to .env by the deploy workflow) and the old ones are removed by PHP itself,
     * see RemoveStaleCacheDirsWarmer.
     */
    public function getCacheDir(): string
    {
        $release = $_SERVER['SENTRY_RELEASE'] ?? $_ENV['SENTRY_RELEASE'] ?? null;

        if (!is_string($release)) {
            return parent::getCacheDir();
        }

        $release = substr((string) preg_replace('/[^a-zA-Z0-9]/', '', $release), 0, 12);

        if ($release === '') {
            return parent::getCacheDir();
        }

        return $this->getProjectDir() . '/var/cache/' . $this->environment . '_' . $release;
    }
}
