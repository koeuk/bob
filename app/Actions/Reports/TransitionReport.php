<?php

namespace App\Actions\Reports;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\User;

/**
 * The single moderation state transition: pending → reviewed | resolved | dismissed.
 *
 * review / resolve / dismiss were three near-identical copies in each of the
 * admin controllers (six in total). They differ only in the target status, the
 * resolution-note rules, and the activity-log name — all captured here.
 */
class TransitionReport
{
    public const PENDING = 'pending';

    public const REVIEWED = 'reviewed';

    public const RESOLVED = 'resolved';

    public const DISMISSED = 'dismissed';

    /** Every status a report can hold (used for queue counts). */
    public const ALL_STATUSES = [self::PENDING, self::REVIEWED, self::RESOLVED, self::DISMISSED];

    /** Activity-log suffix per target status. */
    private const LOG_ACTIONS = [
        self::REVIEWED => 'report.review',
        self::RESOLVED => 'report.resolve',
        self::DISMISSED => 'report.dismiss',
    ];

    /** Validation rules for a given target status. */
    public static function rules(string $status): array
    {
        return match ($status) {
            self::RESOLVED => ['resolution_note' => ['required', 'string', 'max:2000']],
            self::DISMISSED => ['resolution_note' => ['nullable', 'string', 'max:2000']],
            default => [],
        };
    }

    /** Statuses that may still be transitioned. Terminal states are final. */
    private const OPEN_STATUSES = [self::PENDING, self::REVIEWED];

    public function handle(Report $report, string $status, User $actor, ?string $note = null): Report
    {
        // Guard the target status: LOG_ACTIONS has no `pending` key, so a
        // transition back to pending would commit the update and *then* fatal
        // on a null action name.
        abort_unless(
            isset(self::LOG_ACTIONS[$status]),
            422,
            'Unsupported report status: '.$status,
        );

        // Guard the source state: resolved/dismissed are terminal. Without
        // this, re-posting a decided report overwrote resolution_note,
        // reviewed_by and reviewed_at, erasing the original reviewer's record.
        abort_unless(
            in_array($report->status, self::OPEN_STATUSES, true),
            422,
            'This report has already been '.$report->status.'.',
        );

        $attrs = [
            'status' => $status,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ];

        // "reviewed" is only an acknowledgement, so it leaves any existing note
        // untouched; resolve/dismiss both write the note through.
        if ($status !== self::REVIEWED) {
            $attrs['resolution_note'] = $note;
        }

        $report->update($attrs);

        ActivityLog::record(
            self::LOG_ACTIONS[$status],
            $report,
            null,
            $status === self::RESOLVED ? ['note' => $note] : null,
        );

        return $report->fresh();
    }
}
