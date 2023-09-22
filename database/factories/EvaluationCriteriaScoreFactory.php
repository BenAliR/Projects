<?php

namespace Database\Factories;

use App\Models\EvaluationCriteria;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EvaluationCriteriaScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'evaluation_criteria_id' => EvaluationCriteria::factory(),
//            'project_id' => Project::factory(),
//            'user_id' => User::factory(),
                        'user_id' => User::inRandomOrder()->first()->id,

            'project_id' =>Project::inRandomOrder()->first()->id,
            'score' => $this->faker->numberBetween(0, 100),
        ];
    }
}
