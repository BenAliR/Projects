<?php

namespace Database\Factories;


use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InviteAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $studentUserIds = User::where('role', 'invite')->pluck('id')->toArray();
        return [

            'telephone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'photo' => $this->faker->imageUrl(200, 200), // Generate a random image URL (you can change the dimensions)
            'nom' => $this->faker->lastName,
            'prenom' => $this->faker->firstName,
            'adresse' => $this->faker->address,
            'ville' => $this->faker->city,
            'cite' => $this->faker->optional()->word,
            'codepostal' => $this->faker->postcode,
            'etablissement' => $this->faker->company,
            'user_id' => $this->faker->unique()->randomElement($studentUserIds), // Set user_id to null as it will be associated later
            'user_type' => 'App\Models\User', // Set the user type
            'profession' => $this->faker->jobTitle,

//            'user_id' => User::inRandomOrder()->first()->id,
//            'assign_id' => User::inRandomOrder()->first()->id,
//            'project_id' =>Project::inRandomOrder()->first()->id,
        ];
    }
}
