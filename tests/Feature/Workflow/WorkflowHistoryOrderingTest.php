<?php

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Services\Workflow\WorkflowEngine;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    config(['notifications.domain_enabled' => false]);
});

test('workflow histories remain chronological when transition timestamps differ', function () {
    $user = actingAsSuperAdmin();
    $engine = app(WorkflowEngine::class);
    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);

    $engine->apply($leave, $user, 'submit');
    $this->travel(2)->seconds();
    $engine->apply($leave->fresh(), $user, 'approve', 'Looks good');

    $histories = $leave->fresh()->workflowInstance->histories()->get();

    expect($histories)->toHaveCount(2)
        ->and($histories->pluck('action')->all())->toBe(['submit', 'approve'])
        ->and($histories[0]->from_status)->toBe('draft')
        ->and($histories[0]->to_status)->toBe('pending')
        ->and($histories[1]->action)->toBe('approve')
        ->and($histories[1]->comment)->toBe('Looks good');
});
