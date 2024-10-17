<?php

declare(strict_types=1);

namespace CeskaKruta\Web;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class CeskaKrutaKernel extends BaseKernel
{
    use MicroKernelTrait;
}
