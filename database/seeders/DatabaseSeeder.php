<?php

namespace Database\Seeders;


use App\Models\DemandeInscription;
use App\Models\EvaluationCriteriaScore;
use App\Models\InviteAttachment;
use App\Models\Meeting;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        // Seed DemandeInscription
     //   DemandeInscription::factory(68)->create();
      //  EvaluationCriteriaScore::factory(200)->create();
        // Seed User
     //  User::factory(50)->create();
      //  \App\Models\User::factory(200)->create();
      //
      //    Task::factory(800)->create();

      //  Team::factory(400)->create();
        Notification::factory(1000)->create();
       // Meeting::factory(1000)->create();
      //  Reminder::factory(200)->create();
    //    InviteAttachment::factory(70)->create();
       // Project::factory(200)->create();
        // Seed relationships between teams and users
//        $teams = Team::all();
//        $users = User::whereIn('role', ['invite'])->get();
//        foreach ($teams as $team) {
//            $randomUsers = $users->random(rand(1, 5)); // Adjust the number of users per team
//
//            foreach ($randomUsers as $user) {
//                $team->users()->attach($user);
//            }
//        }

    }
}
