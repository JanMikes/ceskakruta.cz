<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cache;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Runs once per deploy (when the fresh per-release cache dir is built on the first request) and deletes cache dirs
 * of previous releases (`prod`, `prod_old`, `prod_<commit>`). Must run as PHP – the FTP user has no permission
 * to delete files PHP created. Failures are ignored, the next deploy retries.
 */
final readonly class RemoveStaleCacheDirsWarmer implements CacheWarmerInterface
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $current = basename($cacheDir);

        // Not a per-release cache dir (local dev, tests) → nothing to clean
        if (!str_starts_with($current, $this->environment . '_')) {
            return [];
        }

        $filesystem = new Filesystem();

        foreach (glob(dirname($cacheDir) . '/' . $this->environment . '*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);

            if ($name === $current) {
                continue;
            }

            if ($name !== $this->environment && !str_starts_with($name, $this->environment . '_')) {
                continue;
            }

            try {
                $filesystem->remove($dir);
            } catch (IOExceptionInterface) {
                // Partially removed, retried on next deploy
            }
        }

        return [];
    }
}
