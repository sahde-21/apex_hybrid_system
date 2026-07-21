<?php

use App\Enums\ActivityType;
use App\Enums\ActivityVisibility;
use App\Livewire\Activity\ActivityTimeline;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\SalesDocumentEvent;
use App\Models\User;
use App\Services\Activity\ActivityService;
use App\Services\Activity\DocumentTimelineService;
use App\Services\Audit\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    test()->seed(RolePermissionSeeder::class);
    config(['notifications.domain_enabled' => false]);
});

test('activity localization key parity', function () {
    $en = require lang_path('en/scf.php');
    $ar = require lang_path('ar/scf.php');
    $ckb = require lang_path('ckb/scf.php');

    expect(array_keys($ar['activity']))->toEqual(array_keys($en['activity']))
        ->and(array_keys($ckb['activity']))->toEqual(array_keys($en['activity']))
        ->and(array_keys($ar['workflow']))->toEqual(array_keys($en['workflow']))
        ->and(array_keys($ar['purchase_workflow']))->toEqual(array_keys($en['purchase_workflow']));
});

test('users can comment mention and reply with notifications', function () {
    $actor = actingAsSuperAdmin();
    $mentioned = User::factory()->create(['name' => 'Timeline Mentor', 'is_active' => true]);
    $mentioned->syncPermissions(['activities.read', 'notifications.read']);

    $quotation = Quotation::factory()->create();

    $service = app(ActivityService::class);
    $comment = $service->addComment($quotation, $actor, 'Please review @Timeline Mentor thanks');

    expect($comment->type)->toBe(ActivityType::Comment)
        ->and($comment->mentions)->toHaveCount(1)
        ->and($comment->mentions->first()->user_id)->toBe($mentioned->id);

    expect($mentioned->notifications()->where('data->event', 'activity.mentioned')->count())->toBe(1);

    $replyAuthor = actingAsUserWithPermissions([
        'quotations.read',
        'activities.read',
        'activities.comment',
        'activities.edit_own',
        'activities.delete_own',
    ]);

    $reply = $service->addComment($quotation, $replyAuthor, 'Following up', $comment->id);
    expect($reply->parent_id)->toBe($comment->id);
    expect($actor->notifications()->where('data->event', 'activity.reply')->count())->toBeGreaterThan(0);
});

test('internal notes are hidden without permission', function () {
    $manager = actingAsSuperAdmin();
    $quotation = Quotation::factory()->create();

    app(ActivityService::class)->addComment($quotation, $manager, 'Confidential', null, true);

    $viewer = actingAsUserWithPermissions([
        'quotations.read',
        'activities.read',
        'activities.comment',
    ]);

    $entries = app(DocumentTimelineService::class)->forSubject($quotation, $viewer, 1, 50);
    $types = collect($entries->items())->pluck('type');
    expect($types)->not->toContain(ActivityType::InternalNote);

    $staff = actingAsUserWithPermissions([
        'quotations.read',
        'activities.read',
        'activities.internal_note',
    ]);

    $entries = app(DocumentTimelineService::class)->forSubject($quotation, $staff, 1, 50);
    expect(collect($entries->items())->pluck('type'))->toContain(ActivityType::InternalNote);
});

test('users can edit and delete only own comments and system events are immutable', function () {
    $author = actingAsUserWithPermissions([
        'quotations.read',
        'activities.read',
        'activities.comment',
        'activities.edit_own',
        'activities.delete_own',
    ]);
    $quotation = Quotation::factory()->create();
    $service = app(ActivityService::class);

    $comment = $service->addComment($quotation, $author, 'Mine');
    $service->updateComment($comment, $author, 'Mine edited');
    expect($comment->fresh()->body)->toBe('Mine edited')
        ->and($comment->fresh()->edited_at)->not->toBeNull();

    $other = actingAsUserWithPermissions([
        'quotations.read',
        'activities.read',
        'activities.comment',
        'activities.edit_own',
        'activities.delete_own',
    ]);

    expect(fn () => $service->updateComment($comment->fresh(), $other, 'Hack'))
        ->toThrow(HttpException::class);

    $system = Activity::query()->create([
        'subject_type' => $quotation->getMorphClass(),
        'subject_id' => $quotation->id,
        'type' => ActivityType::SystemEvent,
        'event_key' => 'system.test',
        'user_id' => $author->id,
        'title' => 'System',
        'body' => 'Nope',
        'visibility' => ActivityVisibility::Public,
        'is_system' => true,
    ]);

    expect(fn () => $service->deleteComment($system, $author))
        ->toThrow(ValidationException::class);

    $service->deleteComment($comment->fresh(), $author);
    expect(Activity::query()->find($comment->id))->toBeNull();
});

test('unified timeline merges sales events and comments newest first', function () {
    $user = actingAsSuperAdmin();
    $quotation = Quotation::factory()->create();

    SalesDocumentEvent::query()->create([
        'document_type' => $quotation->getMorphClass(),
        'document_id' => $quotation->id,
        'event' => 'approved',
        'from_status' => 'sent',
        'to_status' => 'accepted',
        'user_id' => $user->id,
        'created_at' => now()->subMinute(),
    ]);

    app(ActivityService::class)->addComment($quotation, $user, 'Latest comment');

    $entries = app(DocumentTimelineService::class)->forSubject($quotation, $user, 1, 20);
    expect($entries->total())->toBeGreaterThanOrEqual(2);
    expect($entries->items()[0]->type)->toBe(ActivityType::Comment);
});

test('audit logs are immutable even for privileged users', function () {
    $user = actingAsSuperAdmin();
    $log = AuditLog::query()->create([
        'user_id' => $user->id,
        'auditable_type' => Quotation::class,
        'auditable_id' => 1,
        'action' => 'updated',
        'old_values' => ['total_amount' => 10],
        'new_values' => ['total_amount' => 20],
        'ip_address' => '127.0.0.1',
    ]);

    expect(fn () => $log->update(['action' => 'hacked']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
    expect(fn () => app(AuditLogService::class)->delete())->toThrow(LogicException::class);
});

test('activity center and audit log pages are reachable', function () {
    actingAsSuperAdmin();

    $this->get(route('activities.index'))->assertOk();
    $this->get(route('audit-logs.index'))->assertOk();

    $log = AuditLog::query()->create([
        'user_id' => auth()->id(),
        'auditable_type' => Quotation::class,
        'auditable_id' => 99,
        'action' => 'created',
        'old_values' => null,
        'new_values' => ['status' => 'draft'],
    ]);

    $this->get(route('audit-logs.show', $log))->assertOk();
});

test('activity timeline livewire posts comments on quotations', function () {
    $user = actingAsSuperAdmin();
    $quotation = Quotation::factory()->create();

    Livewire::actingAs($user)
        ->test(ActivityTimeline::class, ['subject' => $quotation])
        ->set('body', 'Hello timeline')
        ->call('submit')
        ->assertSuccessful();

    expect(Activity::query()->where('subject_id', $quotation->id)->where('body', 'Hello timeline')->exists())->toBeTrue();
});

test('attachment upload records activity via dms', function () {
    Storage::fake(config('documents.disk', 'local'));
    $user = actingAsSuperAdmin();
    $quotation = Quotation::factory()->create();

    $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');
    $activity = app(ActivityService::class)->attachFile($quotation, $user, $file, 'See attached');

    expect($activity->type)->toBe(ActivityType::Attachment)
        ->and($activity->managed_document_id)->not->toBeNull()
        ->and($activity->document)->not->toBeNull();
});

test('unauthorized users cannot open activity center', function () {
    actingAsUserWithPermissions(['dashboard.read']);

    $this->get(route('activities.index'))->assertForbidden();
});

test('sales and purchasing show pages embed the activity timeline', function () {
    actingAsSuperAdmin();

    $quotation = Quotation::factory()->create();
    $this->get(route('quotations.show', $quotation))
        ->assertOk()
        ->assertSee(__('scf.activity.timeline_title'), false);

    $pr = PurchaseRequest::factory()->create();
    $this->get(route('purchase-requests.show', $pr))
        ->assertOk()
        ->assertSee(__('scf.activity.timeline_title'), false);
});
