<?php

use App\Enums\LeaveRequestStatus;
use App\Enums\NotificationCategory;
use App\Enums\WorkflowApprovalStatus;
use App\Livewire\Workflow\WorkflowPanel;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkflowDemoDocument;
use App\Models\WorkflowHistory;
use App\Services\Workflow\WorkflowEngine;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    config(['notifications.domain_enabled' => false]);
});

test('workflow localization key parity', function () {
    $en = require lang_path('en/scf.php');
    $ar = require lang_path('ar/scf.php');
    $ckb = require lang_path('ckb/scf.php');

    $enKeys = array_keys($en['workflow']);
    expect(array_keys($ar['workflow']))->toEqual($enKeys)
        ->and(array_keys($ckb['workflow']))->toEqual($enKeys);
});

test('leave request workflow submit approve reject return cancel and history', function () {
    $user = actingAsSuperAdmin();
    $engine = app(WorkflowEngine::class);

    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);

    $engine->ensureInstance($leave);
    expect($engine->can($leave, $user, 'submit'))->toBeTrue();

    $engine->apply($leave, $user, 'submit');
    $leave->refresh();
    expect($leave->status)->toBe(LeaveRequestStatus::Pending);

    $engine->apply($leave, $user, 'approve', 'Looks good');
    $leave->refresh();
    expect($leave->status)->toBe(LeaveRequestStatus::Approved);

    $histories = $leave->workflowInstance->histories()->orderBy('id')->get();
    expect($histories)->toHaveCount(2)
        ->and($histories[0]->action)->toBe('submit')
        ->and($histories[0]->from_status)->toBe('draft')
        ->and($histories[0]->to_status)->toBe('pending')
        ->and($histories[0]->user_id)->toBe($user->id)
        ->and($histories[1]->action)->toBe('approve')
        ->and($histories[1]->approval_level)->toBe(1)
        ->and($histories[1]->comment)->toBe('Looks good');

    $leave2 = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leave2, $user, 'submit');
    $engine->apply($leave2, $user, 'reject', 'Insufficient coverage');
    expect($leave2->fresh()->status)->toBe(LeaveRequestStatus::Rejected);

    $leave3 = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leave3, $user, 'submit');
    $engine->apply($leave3, $user, 'return', 'Need more details');
    expect($leave3->fresh()->status)->toBe(LeaveRequestStatus::Draft);

    $leave4 = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leave4, $user, 'cancel');
    expect($leave4->fresh()->status)->toBe(LeaveRequestStatus::Cancelled);

    $this->get(route('leave-requests.show', $leave))->assertOk();
});

test('workflow authorization hides and blocks unauthorized actions', function () {
    $submitter = actingAsUserWithPermissions([
        'leave-requests.read',
        'leave-requests.update',
        'workflow.submit',
        'workflow.cancel',
    ]);

    $engine = app(WorkflowEngine::class);
    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);

    $actions = collect($engine->availableActions($leave, $submitter))->pluck('action');
    expect($actions)->toContain('submit')
        ->and($actions)->not->toContain('approve');

    $engine->apply($leave, $submitter, 'submit');
    $leave->refresh();

    expect(fn () => $engine->apply($leave, $submitter, 'approve'))
        ->toThrow(HttpException::class);

    $approver = actingAsUserWithPermissions([
        'leave-requests.read',
        'leave-requests.approve',
        'workflow.approve',
        'workflow.reject',
    ]);

    expect($engine->can($leave->fresh(), $approver, 'approve'))->toBeTrue();
    $engine->apply($leave->fresh(), $approver, 'approve');
    expect($leave->fresh()->status)->toBe(LeaveRequestStatus::Approved);
});

test('reject requires a comment', function () {
    $user = actingAsSuperAdmin();
    $engine = app(WorkflowEngine::class);
    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);

    $engine->apply($leave, $user, 'submit');

    expect(fn () => $engine->apply($leave->fresh(), $user, 'reject'))
        ->toThrow(ValidationException::class);
});

test('sequential multi-level approval requires all levels', function () {
    $user = actingAsSuperAdmin();
    $engine = app(WorkflowEngine::class);

    $doc = WorkflowDemoDocument::query()->create([
        'reference_number' => 'WF-SEQ-001',
        'definition_key' => 'demo-multi-level',
        'status' => 'draft',
    ]);

    $engine->apply($doc, $user, 'submit');
    expect($doc->fresh()->status)->toBe('pending_approval');

    $engine->apply($doc->fresh(), $user, 'approve', 'Level 1 OK');
    $doc->refresh();
    expect($doc->status)->toBe('pending_approval');

    $instance = $doc->workflowInstance()->with('approvals')->first();
    expect($instance->approvals)->toHaveCount(2)
        ->and($instance->approvals->firstWhere('level', 1)->status)->toBe(WorkflowApprovalStatus::Approved)
        ->and($instance->approvals->firstWhere('level', 2)->status)->toBe(WorkflowApprovalStatus::Pending)
        ->and($instance->current_approval_level)->toBe(2);

    $partial = WorkflowHistory::query()
        ->where('workflow_instance_id', $instance->id)
        ->where('action', 'approve')
        ->where('from_status', 'pending_approval')
        ->where('to_status', 'pending_approval')
        ->first();
    expect($partial)->not->toBeNull()
        ->and($partial->meta['partial'] ?? false)->toBeTrue();

    $engine->apply($doc->fresh(), $user, 'approve', 'Level 2 OK');
    expect($doc->fresh()->status)->toBe('approved');
});

test('parallel approval completes when all levels approve', function () {
    $user = actingAsSuperAdmin();
    $engine = app(WorkflowEngine::class);

    $doc = WorkflowDemoDocument::query()->create([
        'reference_number' => 'WF-PAR-001',
        'definition_key' => 'demo-parallel',
        'status' => 'pending_approval',
    ]);

    $engine->ensureInstance($doc);

    $engine->apply($doc, $user, 'approve', 'Finance');
    expect($doc->fresh()->status)->toBe('pending_approval');

    $engine->apply($doc->fresh(), $user, 'approve', 'Ops');
    expect($doc->fresh()->status)->toBe('approved');
});

test('workflow notifications fire on submit approve reject cancel and return', function () {
    $actor = actingAsSuperAdmin();
    $recipient = User::factory()->create(['is_active' => true]);
    $recipient->syncPermissions([
        'leave-requests.read',
        'leave-requests.approve',
        'workflow.approve',
    ]);

    $engine = app(WorkflowEngine::class);

    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leave, $actor, 'submit');

    expect($recipient->notifications()->where('data->event', 'workflow.submitted')->count())->toBeGreaterThan(0);

    $leaveB = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leaveB, $actor, 'submit');
    $engine->apply($leaveB->fresh(), $actor, 'approve');
    expect($recipient->notifications()->where('data->event', 'workflow.approved')->count())->toBeGreaterThan(0);

    $leaveC = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leaveC, $actor, 'submit');
    $engine->apply($leaveC->fresh(), $actor, 'reject', 'No');
    expect($recipient->notifications()->where('data->event', 'workflow.rejected')->count())->toBeGreaterThan(0);

    $leaveD = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leaveD, $actor, 'cancel');
    expect($recipient->notifications()->where('data->event', 'workflow.cancelled')->count())->toBeGreaterThan(0);

    $leaveE = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);
    $engine->apply($leaveE, $actor, 'submit');
    $engine->apply($leaveE->fresh(), $actor, 'return', 'Fix dates');
    expect($recipient->notifications()->where('data->event', 'workflow.returned')->count())->toBeGreaterThan(0);

    $sample = $recipient->notifications()->where('data->event', 'workflow.submitted')->first();
    expect($sample->data['category'] ?? null)->toBe(NotificationCategory::Workflow->value);
});

test('workflow panel livewire applies transitions', function () {
    $user = actingAsSuperAdmin();
    $leave = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Draft]);

    Livewire::actingAs($user)
        ->test(WorkflowPanel::class, ['document' => $leave])
        ->call('requestAction', 'submit')
        ->assertSuccessful();

    expect($leave->fresh()->status)->toBe(LeaveRequestStatus::Pending);
});

test('hr role receives workflow permissions', function () {
    $user = actingAsRole('hr');

    expect($user->can('workflow.submit'))->toBeTrue()
        ->and($user->can('workflow.approve'))->toBeTrue()
        ->and($user->can('workflow.reject'))->toBeTrue()
        ->and($user->can('leave-requests.approve'))->toBeTrue();
});
