<?php

namespace Database\Seeders;

use App\Models\TypeUser;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $types_user = [
            ['name' => 'Administrador'],
            ['name' => 'Operador'],
        ];

        foreach ($types_user as $type_user) {
            TypeUser::create($type_user);
        }

        $warehouses = [
            ['name' => 'Two Brothers Cuatro Vientos', 'location' => 'Alvaro Obregon s/n, Cuatro Vientos, 56589,  Ixtapaluca, EDOMEX'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }

        User::create([
            'name' => 'Admin',
            'email' => 'eduarlun4@gmail.com',
            'type_user_id' => 1,
            'warehouse_id' => 1,
            'email_verified_at' => now(),
            'password' => bcrypt('Admin123#'),
        ]);
    }
}
