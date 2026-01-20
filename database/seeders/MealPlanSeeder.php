<?php

namespace Database\Seeders;

use App\Models\MealPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MealPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => config('seeder.user.email')],
            [
                'name' => config('seeder.user.name'),
                'password' => config('seeder.user.password'),
            ]
        );

        $startOfWeek = Carbon::today()->startOfWeek();
        $plans = [
            [
                'name' => 'This Week',
                'start_date' => $startOfWeek->copy(),
                'end_date' => $startOfWeek->copy()->addDays(6),
            ],
            [
                'name' => 'Next Week',
                'start_date' => $startOfWeek->copy()->addWeek(),
                'end_date' => $startOfWeek->copy()->addWeek()->addDays(6),
            ],
            [
                'name' => 'Family Favorites',
                'start_date' => $startOfWeek->copy()->subWeek(),
                'end_date' => $startOfWeek->copy()->subWeek()->addDays(6),
            ],
        ];

        foreach ($plans as $plan) {
            MealPlan::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $plan['name'],
                ],
                [
                    'start_date' => $plan['start_date']->toDateString(),
                    'end_date' => $plan['end_date']->toDateString(),
                ]
            );
        }
    }
}
