<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $parties = [
            [
                'party_type' => 'supplier',
                'name' => 'Dhaka Auto Supply Ltd.',
                'phone' => '01711000001',
                'email' => 'sales@dhakaautosupply.test',
                'address' => 'Tejgaon Industrial Area, Dhaka',
            ],
            [
                'party_type' => 'supplier',
                'name' => 'Chattogram Parts Import',
                'phone' => '01711000002',
                'email' => 'orders@ctgparts.test',
                'address' => 'Agrabad, Chattogram',
            ],
            [
                'party_type' => 'supplier',
                'name' => 'Metro Engine Components',
                'phone' => '01711000003',
                'email' => 'info@metroengine.test',
                'address' => 'Mirpur, Dhaka',
            ],
            [
                'party_type' => 'customer',
                'name' => 'Rahman Motors',
                'phone' => '01811000001',
                'email' => 'rahmanmotors@example.test',
                'address' => 'Moghbazar, Dhaka',
            ],
            [
                'party_type' => 'customer',
                'name' => 'City Auto Workshop',
                'phone' => '01811000002',
                'email' => 'cityauto@example.test',
                'address' => 'Uttara, Dhaka',
            ],
            [
                'party_type' => 'customer',
                'name' => 'Nabil Spare Parts',
                'phone' => '01811000003',
                'email' => 'nabilparts@example.test',
                'address' => 'Jatrabari, Dhaka',
            ],
        ];

        foreach ($parties as $party) {
            Party::updateOrCreate(
                ['email' => $party['email']],
                $party
            );
        }
    }
}
