<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'fullname' => $this->faker->name,
            'nom' => $this->faker->lastName,
            'prenom' =>  $this->faker->firstName,
            'email' =>  $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // Change 'password' to the desired default password
            'wh_id' =>  $this->faker->randomNumber(),
            'role' => $this->faker->randomElement(['invite', 'monitor', 'etudiant']), // Set the desired default role for users
            'last_login_at' =>  $this->faker->dateTimeThisYear(),
            'last_login_ip' =>  $this->faker->ipv4,
            'banned' =>  $this->faker->boolean,
            'banned_at' =>  $this->faker->boolean ?  $this->faker->dateTimeThisYear() : null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
