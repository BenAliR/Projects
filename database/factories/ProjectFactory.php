<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
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
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'project_avatar' => $this->faker->imageUrl(200, 200, 'project'),
            'project_status' => $this->faker->randomElement(['en attente', 'en cours de traitement', 'active', 'inactive']),
            'type' => $this->faker->randomElement(['Projet de Classe', 'Projet Personnel', 'Projet de Fin d\' Etudes']),
            'dev_technologie' => $this->faker->randomElement(['ReactJS', 'Angular', 'Vue','HTML5']),
            'domaine' => $this->faker->word,
            'user_id' => $users->random()->id,
            'team_size' => $this->faker->randomElement(['1-2', '2-5', '5-10','10+']),
            'slug' => Str::slug($this->faker->sentence),
            'duedate' => $this->faker->dateTimeBetween('now', '+6 months'),
//            'team_id' => Team::inRandomOrder()->first()->id,
            'team_id' => Team::factory(),
        ];
    }
}
