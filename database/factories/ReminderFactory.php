<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


class ReminderFactory extends Factory
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

            'reminder_datetime' => $this->faker->dateTimeBetween('now', '+1 month'),
            'user_id' =>Task::inRandomOrder()->first()->project()->first()->user_id,
            'checked' => 0,
            'task_id' =>Task::inRandomOrder()->first()->id,
        ];
    }
}
