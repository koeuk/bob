<?php

namespace App\Actions\Concerns;

use Illuminate\Http\Request;

/**
 * Shared page-size resolution for the admin list actions.
 *
 * `$request->integer('per_page')` happily returns a negative, and a negative
 * limit is silently ignored by the query builder — which on MySQL emits an
 * `OFFSET` with no `LIMIT` (a syntax error → 500) and on other drivers would
 * return the entire table. Clamping keeps the page size sane on every driver.
 */
trait PaginatesLists
{
    /** Hard ceiling so one request cannot pull an unbounded page. */
    private const MAX_PER_PAGE = 100;

    protected function perPage(Request $request, int $default): int
    {
        $requested = $request->integer('per_page', $default);

        if ($requested < 1) {
            $requested = $default;
        }

        return min($requested, self::MAX_PER_PAGE);
    }
}
