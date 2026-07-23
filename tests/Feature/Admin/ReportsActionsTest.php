<?php

use App\Actions\Reports\ListReports;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Characterization tests for app/Actions/Reports/*, exercised through the JSON
 * admin API. The Inertia admin panel calls the same Actions.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->moderator = User::factory()->create();
    $this->moderator->assignRole('admin');
    Sanctum::actingAs($this->moderator);
});

function makeReport(string $status = 'pending'): Report
{
    $reporter = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $author->id]);

    return Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => Post::class,
        'reportable_id' => $post->id,
        'reason' => 'Spam',
        'status' => $status,
    ]);
}

it('lists the queue with counts for every status', function () {
    makeReport('pending');
    makeReport('resolved');

    $response = $this->getJson('/api/admin/reports')->assertOk();

    expect($response->json('counts'))
        ->toHaveKeys(['pending', 'reviewed', 'resolved', 'dismissed'])
        ->and($response->json('counts.pending'))->toBe(1)
        ->and($response->json('counts.resolved'))->toBe(1)
        ->and($response->json('counts.reviewed'))->toBe(0);
});

it('marks a report reviewed without touching the resolution note', function () {
    $report = makeReport();
    $report->update(['resolution_note' => 'keep me']);

    $this->postJson("/api/admin/reports/{$report->uuid}/review")->assertOk();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe('reviewed')
        ->and($fresh->resolution_note)->toBe('keep me')
        ->and($fresh->reviewed_by)->toBe($this->moderator->id)
        ->and(ActivityLog::where('action', 'report.review')->exists())->toBeTrue();
});

it('resolves a report and requires a note', function () {
    $report = makeReport();

    $this->postJson("/api/admin/reports/{$report->uuid}/resolve", [])
        ->assertStatus(422);

    $this->postJson("/api/admin/reports/{$report->uuid}/resolve", [
        'resolution_note' => 'Content removed.',
    ])->assertOk();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe('resolved')
        ->and($fresh->resolution_note)->toBe('Content removed.')
        ->and(ActivityLog::where('action', 'report.resolve')->exists())->toBeTrue();
});

it('dismisses a report with an optional note', function () {
    $report = makeReport();

    $this->postJson("/api/admin/reports/{$report->uuid}/dismiss")->assertOk();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe('dismissed')
        ->and($fresh->resolution_note)->toBeNull()
        ->and($fresh->reviewed_at)->not->toBeNull()
        ->and(ActivityLog::where('action', 'report.dismiss')->exists())->toBeTrue();
});

it('returns zeroed counts when the queue is empty', function () {
    expect(ListReports::counts())->toBe([
        'pending' => 0,
        'reviewed' => 0,
        'resolved' => 0,
        'dismissed' => 0,
    ]);
});
