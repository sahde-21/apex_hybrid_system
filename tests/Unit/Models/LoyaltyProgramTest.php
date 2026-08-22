<?php

use App\Models\Contact;
use App\Models\LoyaltyBalance;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRedemption;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes has-many relations for loyalty program foreign keys', function () {
    $program = new LoyaltyProgram;

    expect($program->loyaltyBalances())->toBeInstanceOf(HasMany::class)
        ->and($program->loyaltyRedemptions())->toBeInstanceOf(HasMany::class);
});

it('loads inverse records through loyalty program relations', function () {
    $program = LoyaltyProgram::factory()->create();
    $contact = Contact::factory()->create();

    LoyaltyBalance::query()->create([
        'contact_id' => $contact->id,
        'loyalty_program_id' => $program->id,
        'points' => 120,
    ]);
    LoyaltyRedemption::query()->create([
        'contact_id' => $contact->id,
        'loyalty_program_id' => $program->id,
        'points' => 20,
        'reward_label' => 'Discount',
        'status' => 'completed',
    ]);

    $program->load(['loyaltyBalances', 'loyaltyRedemptions']);

    expect($program->loyaltyBalances)->toHaveCount(1)
        ->and($program->loyaltyRedemptions)->toHaveCount(1)
        ->and((float) $program->loyaltyBalances->first()->points)->toBe(120.0);
});
