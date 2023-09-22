<?php

namespace Database\Factories;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Models\Notification;
use App\Models\User;
use App\Models\Team; // Import the Team model if needed
use Illuminate\Database\Eloquent\Factories\Factory;
class NotificationFactory extends Factory
{


    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $notifiableType = $this->faker->randomElement([User::class]); // Adjust based on your notifiable models
   $users = User::whereIn('role', ['etudiant'])->get();; // Get a random notifiable instance
        return [
            'title' => $this->faker->randomElement(['Task Created' , 'Project Status Changed','New Meeting','New Task Assignment']),
            'user_id' =>$users->random()->id,
            'type' => $this->faker->randomElement(['Task' , 'Project','Meeting','Event']),
            'message' => $this->faker->sentence,
            'read' => $this->faker->boolean,
            'read_at' => $this->faker->optional(0.8)->dateTimeThisDecade, // 80% chance of having a read_at timestamp
            'notifiable_type' => $notifiableType,
            'data' => json_encode(['key' => 'value']), // Adjust to store relevant data
        ];
    }

}
