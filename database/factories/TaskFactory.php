<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'priority' => $this->faker->randomElement(['Low', 'Medium', 'High']),
            'slug' => Str::slug($this->faker->sentence),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => $this->faker->randomElement(['0', '1', '2']),
            'type' => $this->faker->randomElement(['Présentation du projet', 'Concept de design', 'Logiques fonctionnelles','Development','Testing']),

//            'user_id' => \App\Models\User::factory(),
//            'assign_id' => \App\Models\User::factory(),
//            'project_id' => \App\Models\Project::factory(),
            'user_id' => Project::inRandomOrder()->first()->user_id,
            'assign_id' =>  Project::inRandomOrder()->first()->user_id,
            'project_id' =>Project::inRandomOrder()->first()->id,
        ];
    }
}
