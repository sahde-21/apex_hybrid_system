<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\PortalSupplier;
use Illuminate\Database\Seeder;

class PortalSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $contact = Contact::query()->firstOrCreate(
            ['email' => 'supplier@scf.com'],
            [
                'name' => 'Portal Demo Supplier',
                'company_name' => 'SCF Supply Co.',
                'type' => ContactType::Supplier,
                'phone' => '+9647500000001',
                'address' => 'Sulaymaniyah, Kurdistan Region',
                'opening_balance' => 0,
            ],
        );

        PortalSupplier::query()->updateOrCreate(
            ['email' => 'supplier@scf.com'],
            [
                'contact_id' => $contact->id,
                'name' => $contact->company_name ?: $contact->name,
                'phone' => $contact->phone,
                'locale' => 'en',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
