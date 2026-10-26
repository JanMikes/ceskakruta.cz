<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Where a grid action returns to: the posted `_target` when it is an admin path (no open redirect), else the dashboard.
 */
final class GridRedirectTarget
{
    public static function fromRequest(Request $request): string
    {
        $target = $request->request->get('_target');

        if (is_string($target) && preg_match('~^/admin(?:[/?#]|$)~', $target) === 1) {
            // Anchor of the action is appended by the caller
            return explode('#', $target, 2)[0];
        }

        return '/admin';
    }
}
