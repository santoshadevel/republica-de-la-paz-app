<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the initial membership plans / passes (idempotent by slug).
     * Prices are in the currency minor unit (Gs, 0 decimals by default).
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free-trial',
                'name' => 'Prueba gratuita',
                'description' => 'Una práctica de cortesía para conocer la República.',
                'price' => 0,
                'sort_order' => 1,
                'rules' => [
                    'credits' => 1,
                    'unlimited' => false,
                    'validity_days' => 7,
                    'cancellation' => ['group_hours' => 1],
                    'included_types' => ['group_class'],
                ],
            ],
            [
                'slug' => 'citizen-pass',
                'name' => 'Pase Ciudadano',
                'description' => '4 prácticas para usar durante el mes.',
                'price' => 350000,
                'sort_order' => 2,
                'rules' => [
                    'credits' => 4,
                    'unlimited' => false,
                    'validity_days' => 30,
                    'cancellation' => ['group_hours' => 1],
                    'included_types' => ['group_class'],
                ],
            ],
            [
                'slug' => 'community-pass',
                'name' => 'Pase Comunidad',
                'description' => '12 prácticas para usar durante el mes.',
                'price' => 400000,
                'sort_order' => 3,
                'rules' => [
                    'credits' => 12,
                    'unlimited' => false,
                    'validity_days' => 30,
                    'cancellation' => ['group_hours' => 1],
                    'included_types' => ['group_class'],
                ],
            ],
            [
                'slug' => 'republic-membership',
                'name' => 'Membresía República',
                'description' => 'Prácticas ilimitadas durante el mes.',
                'price' => 480000,
                'sort_order' => 4,
                'rules' => [
                    'credits' => null,
                    'unlimited' => true,
                    'validity_days' => 30,
                    'cancellation' => ['group_hours' => 1],
                    'included_types' => ['group_class'],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
