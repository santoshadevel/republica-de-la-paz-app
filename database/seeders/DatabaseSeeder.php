<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PlanSeeder::class,
            ActivitySeeder::class,
            RoomSeeder::class,
            PractitionerSeeder::class,
            AccountingCatalogSeeder::class,
            // Full connected demo dataset (students, sales, bookings, events...).
            DemoSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@santosha.test'],
            ['name' => 'Admin Santosha', 'password' => Hash::make('password')],
        );

        $admin->syncRoles([Role::Admin->value]);
    }
}
