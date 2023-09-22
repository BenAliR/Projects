<?php

namespace Database\Factories;

use App\Models\DemandeInscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DemandeInscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DemandeInscription::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $studentUserIds = User::where('role', 'etudiant')->pluck('id')->toArray();

        return [
            'telephone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'photo' => $this->faker->imageUrl(200, 200), // Generate a random image URL (you can change the dimensions)
            'country' => $this->faker->country,
            'typeecole' => $this->faker->randomElement(['Publique', 'Privé']),
            'nom' => $this->faker->lastName,
            'prenom' => $this->faker->firstName,
            'adresse' => $this->faker->address,
            'adresse2' => $this->faker->optional()->secondaryAddress,
            'ville' => $this->faker->city,
            'cite' => $this->faker->optional()->word,
            'codepostal' => $this->faker->postcode,
            'copie1' => $this->faker->imageUrl(500, 500), // Generate a random image URL (you can change the dimensions)
            'copie2' => $this->faker->imageUrl(500, 500), // Generate a random image URL (you can change the dimensions)
            'copie3' => $this->faker->imageUrl(500, 500), // Generate a random image URL (you can change the dimensions)
            'copie4' => $this->faker->imageUrl(500, 500), // Generate a random image URL (you can change the dimensions)
            'demande_status' => $this->faker->randomElement(['en cours de traitement', 'rejetée', 'approuvée']),
            'etablisement' => $this->faker->company,
            'user_id' => $this->faker->unique()->randomElement($studentUserIds), // Set user_id to null as it will be associated later
            'user_type' => 'App\Models\User', // Set the user type
            'country_code' => $this->faker->countryCode,
            'tel_format_national' => $this->faker->phoneNumber,
        ];
    }
    public function configure()
    {
        return $this->afterCreating(function (DemandeInscription $demandeInscription) {
            // Perform any additional actions after creating the model instance.
        });
    }

}
