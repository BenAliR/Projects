<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Get users with roles 'student' or 'invited'
        $users = User::whereIn('role', ['etudiant', 'invite'])->get();

        return [
            'team_id' => function () {
                return Team::inRandomOrder()->first()->id;
            },
            'user_id' => $users->random()->id,
        ];
    }
}
