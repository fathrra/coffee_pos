<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT Kopi Nusantara',
                'phone' => '0812-3456-7890',
                'email' => 'sales@kopinusantara.co.id',
                'address' => 'Jl. Raya Kopi No. 12, Bandung',
                'notes' => 'Kopi arabica & robusta.',
            ],
            [
                'name' => 'CV Susu Segar',
                'phone' => '0813-2233-4455',
                'email' => 'order@sususegar.co.id',
                'address' => 'Jl. Dairy Farm No. 5, Bogor',
                'notes' => 'Susu UHT & creamer.',
            ],
            [
                'name' => 'Toko Kemasan Prima',
                'phone' => '0821-9988-7766',
                'email' => 'cs@kemasanprima.co.id',
                'address' => 'Jl. Industri No. 88, Tangerang',
                'notes' => 'Cup, tutup, sedotan.',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        }
    }
}