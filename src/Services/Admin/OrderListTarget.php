<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Back to the order list page the action was posted from (`_target`, filter + page kept), never outside the list.
 */
final class OrderListTarget
{
    public static function fromRequest(Request $request): string
    {
        $target = $request->request->get('_target');

        if (is_string($target) && preg_match('~^/admin/objednavky(?:\?[^#]*)?$~', $target) === 1) {
            return $target;
        }

        return '/admin/objednavky';
    }
}
