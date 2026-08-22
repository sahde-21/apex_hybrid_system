<?php

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\AccountingAuditLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Budget;
use App\Models\DocumentFolder;
use App\Models\FixedAsset;
use App\Models\FloorPlan;
use App\Models\IntelligenceAlert;
use App\Models\IntelligenceRecommendation;
use App\Models\IntelligenceSnapshot;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ManagedDocument;
use App\Models\PosRegister;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes has-many relations for every branch foreign key', function () {
    $branch = new Branch;

    expect($branch->saleOrders())->toBeInstanceOf(HasMany::class)
        ->and($branch->purchaseOrders())->toBeInstanceOf(HasMany::class)
        ->and($branch->productionOrders())->toBeInstanceOf(HasMany::class)
        ->and($branch->posRegisters())->toBeInstanceOf(HasMany::class)
        ->and($branch->attendances())->toBeInstanceOf(HasMany::class)
        ->and($branch->shifts())->toBeInstanceOf(HasMany::class)
        ->and($branch->floorPlans())->toBeInstanceOf(HasMany::class)
        ->and($branch->budgets())->toBeInstanceOf(HasMany::class)
        ->and($branch->fixedAssets())->toBeInstanceOf(HasMany::class)
        ->and($branch->accounts())->toBeInstanceOf(HasMany::class)
        ->and($branch->journalEntries())->toBeInstanceOf(HasMany::class)
        ->and($branch->journalEntryLines())->toBeInstanceOf(HasMany::class)
        ->and($branch->documentFolders())->toBeInstanceOf(HasMany::class)
        ->and($branch->managedDocuments())->toBeInstanceOf(HasMany::class)
        ->and($branch->accountingAuditLogs())->toBeInstanceOf(HasMany::class)
        ->and($branch->intelligenceSnapshots())->toBeInstanceOf(HasMany::class)
        ->and($branch->intelligenceAlerts())->toBeInstanceOf(HasMany::class)
        ->and($branch->intelligenceRecommendations())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through branch relations', function () {
    $branch = Branch::factory()->create();

    SaleOrder::factory()->create(['branch_id' => $branch->id]);
    PurchaseOrder::factory()->create(['branch_id' => $branch->id]);
    ProductionOrder::factory()->create(['branch_id' => $branch->id]);
    PosRegister::factory()->create(['branch_id' => $branch->id]);
    Attendance::factory()->create(['branch_id' => $branch->id]);
    Shift::factory()->create(['branch_id' => $branch->id]);
    FloorPlan::factory()->create(['branch_id' => $branch->id]);
    Budget::factory()->create(['branch_id' => $branch->id]);
    FixedAsset::factory()->create(['branch_id' => $branch->id]);

    $account = Account::query()->create([
        'code' => 'BR-1000',
        'name' => 'Branch cash',
        'type' => AccountType::Asset,
        'normal_balance' => NormalBalance::Debit,
        'branch_id' => $branch->id,
    ]);

    $journalEntry = JournalEntry::factory()->create(['branch_id' => $branch->id]);
    JournalEntryLine::query()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'line_number' => 1,
        'debit' => 10,
        'credit' => 0,
        'base_debit' => 10,
        'base_credit' => 0,
        'branch_id' => $branch->id,
    ]);

    DocumentFolder::query()->create([
        'name' => 'Branch folder',
        'branch_id' => $branch->id,
    ]);
    ManagedDocument::factory()->create(['branch_id' => $branch->id]);
    AccountingAuditLog::query()->create([
        'action' => 'posted',
        'branch_id' => $branch->id,
        'created_at' => now(),
    ]);
    IntelligenceSnapshot::query()->create([
        'type' => 'executive',
        'branch_id' => $branch->id,
        'payload' => ['ok' => true],
        'generated_at' => now(),
    ]);
    IntelligenceAlert::factory()->create(['branch_id' => $branch->id]);
    IntelligenceRecommendation::factory()->create(['branch_id' => $branch->id]);

    $branch->load([
        'saleOrders',
        'purchaseOrders',
        'productionOrders',
        'posRegisters',
        'attendances',
        'shifts',
        'floorPlans',
        'budgets',
        'fixedAssets',
        'accounts',
        'journalEntries',
        'journalEntryLines',
        'documentFolders',
        'managedDocuments',
        'accountingAuditLogs',
        'intelligenceSnapshots',
        'intelligenceAlerts',
        'intelligenceRecommendations',
    ]);

    expect($branch->saleOrders)->toHaveCount(1)
        ->and($branch->purchaseOrders)->toHaveCount(1)
        ->and($branch->productionOrders)->toHaveCount(1)
        ->and($branch->posRegisters)->toHaveCount(1)
        ->and($branch->attendances)->toHaveCount(1)
        ->and($branch->shifts)->toHaveCount(1)
        ->and($branch->floorPlans)->toHaveCount(1)
        ->and($branch->budgets)->toHaveCount(1)
        ->and($branch->fixedAssets)->toHaveCount(1)
        ->and($branch->accounts)->toHaveCount(1)
        ->and($branch->journalEntries)->toHaveCount(1)
        ->and($branch->journalEntryLines)->toHaveCount(1)
        ->and($branch->documentFolders)->toHaveCount(1)
        ->and($branch->managedDocuments)->toHaveCount(1)
        ->and($branch->accountingAuditLogs)->toHaveCount(1)
        ->and($branch->intelligenceSnapshots)->toHaveCount(1)
        ->and($branch->intelligenceAlerts)->toHaveCount(1)
        ->and($branch->intelligenceRecommendations)->toHaveCount(1);
});
