<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\PortalCustomer;
use Illuminate\Database\Seeder;

class PortalCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $contact = Contact::query()->firstOrCreate(
            ['email' => 'customer@scf.com'],
            [
                'name' => 'Portal Demo Customer',
                'type' => ContactType::Customer,
                'phone' => '+9647500000000',
                'address' => 'Erbil, Kurdistan Region',
                'opening_balance' => 0,
            ],
        );

        PortalCustomer::query()->updateOrCreate(
            ['email' => 'customer@scf.com'],
            [
                'contact_id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'locale' => 'en',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
