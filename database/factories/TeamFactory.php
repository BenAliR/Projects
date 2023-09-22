<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $users = User::whereIn('role', ['etudiant'])->get();
        return [
            'name' => $this->faker->word . ' Team',


            'owner_id' =>   $users->random()->id,
//            'owner_id' =>  \App\Models\User::factory(),
        ];
    }
}
