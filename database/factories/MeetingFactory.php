<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MeetingFactory extends Factory
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

            'start_datetime' => $this->faker->dateTimeBetween('now', '+1 month'),


            'project_id' =>Project::inRandomOrder()->first()->id,
        ];
    }
}
