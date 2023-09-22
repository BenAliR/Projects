<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\InviteAttachment;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class CrudInvitationController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @param $token
     * @return JsonResponse
     */
    public function index($token)
    {
        $invite = Invite::where('token', $token)->first();
        if (!$invite) {
            //if the invite doesn't exist
            return $this->errorResponse(404, ["Invtitation n'existe pas"], "quelque chose s'est mal passé");
        }
        $email = $invite->email;
        // Retrieve the invitation with relationships for the given email
        $invitation = TeamInvitation::where('email', $email)->with(['team.project', 'sender'])->first();

        if ($invitation) {
            return $this->successResponse($invitation, "Enregistrement effectué avec succès!", 201);

        } else {
            return $this->errorResponse(404, ["Aucune invitation en attente trouvée pour le jeton donné"], 'quelque chose s\'est mal passé');

        }

    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $invite = User::where('role', 'invite')->with('encadrement')->find($id);

        if (!$invite) {

            return $this->errorResponse(404, ['invite introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($invite, "Enregistrement effectué avec succès!", 201);

    }

    /**
     * Display resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function store(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [
            'email' => 'required',
            'prenom' => 'required',
            'nom' => 'required',
            'profession' => 'required',
            'etablissement' => 'required',
            'address1' => 'required',
            'ville' => 'required',
            'cite' => 'required',
            'codepostal' => 'required',


        ];
        $niceNames = array(

            'email' => 'required',


        );
        $messages = array(



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        // create the user with the details from the invite
        $password = Str::random(10);
        $user = new User();
        $user->email = $request->email;
        $user->fullname = $request->prenom . ' ' . $request->nom;
        $user->prenom = $request->prenom;
        $user->nom = $request->nom;
        $user->password = bcrypt($password);
        $user->role = 'invite';
        $user->save();
        $fiche =    InviteAttachment::create(      [

            'email' => $request->email,
            'photo' => "/uploads/default/blank.png",
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->address1,
            'ville' => $request->ville,
            'cite' => $request->cite,
            'codepostal' => $request->codepostal,
            'etablissement' => $request->etablissement,
            'profession' => $request->profession,
            'user_id' =>$user->id,
            'user_type' => 'App\Models\User',
        ]);
        $content = 'Email: ' . $request->email . ' Mot de passe: ' . $password;
        $body = [
            'name' => $request->prenom,
            'email' => $request->email,
            'password' => $password,
            'content' => $content,
            'title' => 'Compte créé',
            'emailTitle' => 'Compte Invité'
        ];
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.newaccount', ["data" => $body], function ($message) use ($body) {
            $message
                ->from('riadh@zenhosting.pro', 'Zenhosting')
                ->to($body['email'], $body['name'])
                ->subject('Compte Invité créé!');
        });
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }
    /**
     * Display resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function acceptInvitation(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [
            'email' => 'required',
        'prenom' => 'required',
        'nom' => 'required',
        'profession' => 'required',
        'etablisement' => 'required',
        'address1' => 'required',

        'ville' => 'required',
        'cite' => 'required',
        'codepostal' => 'required',
        'token' => 'required',

        ];
        $niceNames = array(

            'email' => 'required',


        );
        $messages = array(

            'token.required' => 'Veuillez fournir token valide!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        $invite = Invite::where('token', $request->token)->first();
        if (!$invite) {
            //if the invite doesn't exist
            return $this->errorResponse(404, ["Invtitation n'existe pas"], "quelque chose s'est mal passé");
        }
        // create the user with the details from the invite
        $password = Str::random(10);
        $user = new User();
        $user->email = $request->email;
        $user->fullname = $request->prenom . ' ' . $request->nom;
        $user->prenom = $request->prenom;
        $user->nom = $request->nom;
        $user->password = bcrypt($password);
        $user->role = 'invite';
        $user->save();
        $fiche =    InviteAttachment::create(      [

            'email' => $request->email,
            'photo' => "/uploads/default/blank.png",
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->address1,
            'ville' => $request->ville,
            'cite' => $request->cite,
            'codepostal' => $request->codepostal,
            'etablissement' => $request->etablisement,
            'profession' => $request->profession,
            'user_id' =>$user->id,
            'user_type' => 'App\Models\User',
        ]);
        $content = 'Email: ' . $request->email . ' Mot de passe: ' . $password;
        $body = [
            'name' => $request->prenom,
            'email' => $request->email,
            'password' => $password,
            'content' => $content,
            'title' => 'Compte créé',
            'emailTitle' => 'Compte Invité'
        ];
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.newaccount', ["data" => $body], function ($message) use ($body) {
            $message
                ->from('riadh@zenhosting.pro', 'Zenhosting')
                ->to($body['email'], $body['name'])
                ->subject('Compte Invité créé!');
        });
        // delete the invite so it can't be used again
        $invite->delete();

        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);

    }


    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function invitedList()
    {
        // Retrieve all students
        $users = User::where('role', "invite")        ->orderBy('created_at', 'desc')->get();
        return $this->successResponse($users, "Enregistrement effectué avec succès!", 201);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultipleInvited(Request $request)
    {
        $userIdsToDelete = $request->input('user_ids');
        $explode_id = array_map('intval', explode(',', $request->input('user_ids')));
        if (empty($userIdsToDelete)) {
            return $this->errorResponse(400, ['ID les invités non valides fournis.'], 'quelque chose s\'est mal passé');
        }

        try {

            foreach ($explode_id as $userId) {
                $user = User::findOrFail($userId);

                // Handle the relationships and related models.
                $user->teams()->detach(); // Detach the user from all teams they belong to.

                $ownedTeams = $user->ownedTeams;
                foreach ($ownedTeams as $ownedTeam) {
                    $ownedTeam->delete();
                }

                $invite = $user->encadrement;
                if ($invite) {
                    $invite->delete();
                }
                DB::table('oauth_access_tokens')
                    ->where('user_id', $user->id)
                    ->update([
                        'revoked' => true
                    ]);
                // For 'App\Models\Project', you may delete them or take another action, as needed.

                $user->delete();
            }
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Un ou plusieurs invités introuvables.'], 'quelque chose s\'est mal passé');

        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteInvited($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {

                return $this->errorResponse(404, ['Invite introuvable'], 'quelque chose s\'est mal passé');
            }
            // Handle the relationships and related models.
            $user->teams()->detach(); // Detach the user from all teams they belong to.
            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->update([
                    'revoked' => true
                ]);
            $ownedTeams = $user->ownedTeams;
            foreach ($ownedTeams as $ownedTeam) {
                $ownedTeam->delete();
            }

            $invite = $user->encadrement;
            if ($invite) {
                $invite->delete();
            }

            // For 'App\Models\Project', you may delete them or take another action, as needed.

            $user->delete();
            return $this->successResponse([], "Invite effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Invite introuvable'], 'quelque chose s\'est mal passé');
        }
    }
    public function updateInvitedEmail(Request $request, $id)
    {
        try {
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["Invite n'existe pas"], 'quelque chose s\'est mal passé');
            }
            $invite = InviteAttachment::where('user_id',$user->id)->first();
            if (!$invite) {
                return $this->errorResponse(404, ["Invite n'existe pas"], 'quelque chose s\'est mal passé');
            }
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $invite->user_id,
                'password' => 'required',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            if ($request->input('email') == $user->email) {
                return $this->errorResponse(422, ["le mail est le même"], "Une erreur s'est produite lors de la mise à jour");

            }
            // Verify the password
            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(422, ["Mot de passe incorrect"], "Une erreur s'est produite lors de la mise à jour");

            }
            $body = [
                'name'=>$user->prenom,
                'email' => $request->input('email'),
                'email2' => $user->email,
            ];
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.emailupdated',  ["data"=>$body], function($message) use( $body)
            {
                $message
                    ->from('riadh@zenhosting.pro','Zenhosting')
                    ->to($body['email2'] , $body['name'])
                    ->subject("Mise à jour de l'adresse e-mail");
            });
            $invite->email = $request->input('email');
            $invite->save();
            // Update the email
            $user->email = $request->input('email');
            $user->save();

            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500,  ["Une erreur s'est produite lors de la mise à jour"], "Une erreur s'est produite lors de la mise à jour");
        }
    }
    public function updateInvitedPassword(Request $request, $id)
    {
        try {
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["invite n'existe pas"], 'quelque chose s\'est mal passé');
            }
            $invite = InviteAttachment::where('user_id',$user->id)->first();
            if (!$invite) {
                return $this->errorResponse(404, ["invite n'existe pas"], 'quelque chose s\'est mal passé');
            }

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'password' => 'required',
                'newpassword' => 'required',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }

            // Verify the password
            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(422, ["Mot de passe incorrect"], "Une erreur s'est produite lors de la mise à jour");

            }
            $body = [
                'name'=>$user->prenom,
                'email' => $user->email,
                'password' => $user->newpassword,
            ];
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.userpasswordupdated',  ["data"=>$body], function($message) use( $body)
            {
                $message
                    ->from('riadh@zenhosting.pro','Zenhosting')
                    ->to($body['email'] , $body['name'])
                    ->subject("Mot de passe mis à jour");
            });
            // Update password
            $user->password = bcrypt($request->input('newpassword'));
            $user->save();

            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500,  ["Une erreur s'est produite lors de la mise à jour"], "Une erreur s'est produite lors de la mise à jour");
        }
    }

    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateInvited(Request $request,$id)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'prenom' => 'required',
                'nom' => 'required',
                'profession' => 'required',
                'etablissement' => 'required',
                'adresse' => 'required',
                'ville' => 'required',
                'cite' => 'required',
                'codepostal' => 'required',
                'telephone' => 'required',


            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["invite n\'existe pas"], 'quelque chose s\'est mal passé');
            }

            $invite = InviteAttachment::where('user_id',$user->id)->first();
            if (!$invite) {
                return $this->errorResponse(404, ["invite n\'existe pas"], 'quelque chose s\'est mal passé');
            }
            $photourl = "/uploads/default/".$request->input('photo');
            $user->fullname = $request->input('nom') . ' ' . $request->input('prenom');
            $user->nom = $request->input('nom');
            $user->prenom = $request->input('prenom');
            $user->photo = $photourl;
            $user->save();
            // Find invite
            $invite->nom = $request->nom;
          $invite->prenom = $request->prenom;
         $invite->adresse = $request->adresse;
          $invite->ville = $request->ville;
           $invite->cite = $request->cite;
          $invite->codepostal = $request->codepostal;
           $invite->etablissement = $request->etablissement;
          $invite->profession = $request->profession;
            $invite->telephone = $request->telephone;
            $invite->photo = $photourl;

            $invite->save();

            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500, $e, "Une erreur s'est produite lors de la mise à jour");

        }
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function getInvitedProjects($id)
    {
        $invite = User::find($id);

        if (!$invite) {

            return $this->errorResponse(404, ['Invite introuvable'], 'quelque chose s\'est mal passé');
            // Get the authenticated user
        }
        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $invite->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $invite->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);


    }

}
