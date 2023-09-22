<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\EquipeInvitation;

use App\Mail\InviteCreated;
use App\Models\DemandeInscription;
use App\Models\Invite;
use App\Models\Project;

use App\Models\Team;
use App\Models\User;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lexx\ChatMessenger\Models\Thread;
use Mpociot\Teamwork\Facades\Teamwork;
use App\Traits\ApiResponser;
use Snowfire\Beautymail\Beautymail;


class MembersController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getTeamMembers($projectId)
    {
        if ($projectId === null) {
            // Handle the case when $id is undefined or not provided
            // You can return a response or perform any desired logic
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID
        $project = Project::where('slug', $projectId)
            ->with('team.users')
            ->first();
        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the user is a member of the project's team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        // Retrieve the team members of the project's team
        $teamMembers = $project->team->users;
        // Return the team members in a JSON response
        return $this->successResponse($teamMembers,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $slug
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function inviteMember(Request $request, $slug)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID along with its team and owner

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $slug)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Check if the authenticated user is the owner of the team
        if ($user->id !== $project->team->owner->id) {
            return $this->errorResponse(403, ["Seul le propriétaire de l'équipe peut inviter des membres"],"Seul le propriétaire de l'équipe peut inviter des membres" );
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        // Retrieve the user by email
        $newuser = User::where('email', $request->input('email'))->first();

        // Check if the user is already a member of the project's team
        if ($newuser && $project->team->users->contains('id', $user->id)) {
            return $this->errorResponse(404, ["L'utilisateur est déjà membre de l'équipe"],"L'utilisateur est déjà membre de l'équipe" );
        }
        $email= $request->email;
        $projectname = $project->title;
        $teamname = $project->team->name;
        $name = $request->user()->fullname;
        if ($newuser && !$project->team->users->contains('id', $user->id)) {

            if( !Teamwork::hasPendingInvite( $request->email, $project->team) ) {


                Teamwork::inviteToTeam($email, $project->team, function ($invite) use ($teamname, $projectname, $name, $email) {
                    // Send email to user / let them know that they got invited
                    $body = [

                        'email' => $email,
                        'name' => $name,
                    ];
                    // send the email
                    $beautymail = app()->make(Beautymail::class);
                    $beautymail->send('emails.invitationoldmember',  ["data"=>$body], function($message) use( $body)
                    {
                        $message
                            ->from('email@email.com','Company')
                            ->to($body['email'] , $body['email'])
                            ->subject("Invitation pour participer à un projet");
                    });
                });
            }

   }elseif (!$newuser && !$project->team->users->contains('id', $user->id)){

            if( !Teamwork::hasPendingInvite( $request->email, $project->team) ) {
                Teamwork::inviteToTeam($email, $project->team, function ($invite) use ($teamname, $projectname, $name, $email) {
                    // Send email to user / let them know that they got invited

                    do {
                        //generate a random string using Laravel's str_random helper
                        $token = Str::random(30);
                    } //check if the token already exists and if it does, try again
                    while (Invite::where('token', $token)->first());
                    //create a new invite record
                    $invitation = Invite::create([
                        'email' => $email,
                        'token' => $token
                    ]);


                    $body = [
                        'token'=>$token,
                        'email' => $email,
                        'name' => $name,
                    ];
                    // send the email
                    $beautymail = app()->make(Beautymail::class);
                    $beautymail->send('emails.invitation',  ["data"=>$body], function($message) use( $body)
                    {
                        $message
                            ->from('email@email.com','Company')
                            ->to($body['email'] , $body['email'])
                            ->subject("Invitation pour participer à un projet");
                    });



                });
            }
        }
        // Return  a JSON response
        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $slug
     * @return JsonResponse
     */
    public function pendingInvitations(Request $request, $slug)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID along with its team and owner

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $slug)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the user is a member of the project's team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);
        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        $pendingInvitations = $project->team->invitations()->get();

        // Return  a JSON response
        return $this->successResponse($pendingInvitations,"Enregistrement effectué avec succès!",201);
    }





}
