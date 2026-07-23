<?php

namespace App\Actions\Reports;

use App\Models\Report;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Builds the moderation queue (paginated reports + per-status counts).
 * Shared by the Inertia admin panel and the JSON admin API.
 */
class ListReports
{
    /** @return array{reports: \Illuminate\Contracts\Pagination\LengthAwarePaginator, counts: array<string,int>} */
    public function handle(Request $request): array
    {
        $reports = QueryBuilder::for(Report::class)
            ->with(['reporter:id,uuid,name', 'reviewer:id,uuid,name', 'reportable'])
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type', 'reportable_type'),
            ])
            ->allowedSorts(...['created_at', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return [
            'reports' => $reports,
            'counts' => self::counts(),
        ];
    }

    /**
     * Queue counts per status, in one grouped query instead of four COUNTs.
     * Always returns every status key, even when the queue is empty.
     *
     * @return array<string,int>
     */
    public static function counts(): array
    {
        $counts = Report::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = [];
        foreach (TransitionReport::ALL_STATUSES as $status) {
            $result[$status] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }
}
