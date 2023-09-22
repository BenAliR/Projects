<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Snowfire\Beautymail\Beautymail;


class GestionDemandeInscriptionController extends Controller
{
    use ApiResponser;


    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        // Get the currently authenticated user
        $user = Auth::user();

// Retrieve the specific demande of the authenticated user
        $demande = DemandeInscription::where('user_id', $user->id)
            ->first();
        if ($demande) {
            return $this->successResponse($demande, "Enregistrement effectué avec succès!", 201);
        } else {
            return $this->errorResponse(422,  ["demande n\'existe pas"], ' demande n\'existe pas');
        }


    }



    public function UsersTeams  ()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');

        // Get the team IDs owned by the user
        $ownedTeamIds = $user->ownedTeams()->pluck('id');

        // Combine the member and owned team IDs
        $teamIds = $memberTeamIds->concat($ownedTeamIds);

        // Get all members in the user's teams (both members and non-members)
        $members = User::whereHas('teams', function ($query) use ($teamIds) {
            $query->whereIn('id', $teamIds);
        })
            ->orWhereDoesntHave('teams')
            ->with(['teams' => function ($query) use ($teamIds) {
                $query->whereIn('id', $teamIds);
            }])
            ->select('id', 'fullname', 'photo')
            ->distinct()
            ->get();

        return response()->json([
            'members' => $members,
        ]);

    }
    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string',
                'prenom' => 'required|string',
                'adresse' => 'required|string',
                'telephone' =>'required',
                'country' => 'required',
                'typeecole' => 'required',
                'ville' => 'required',
                'cite' =>'required',
                'codepostal' => 'required',
                'etablisement' => 'required',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $demande = DemandeInscription::findOrFail($request->input('id'));
            if (!$demande) {
                return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');

            }
            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if the authenticated user has permission to update the profile
            if ($user->id !==  intval($demande->user_id)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $photourl = "/uploads/default/".$request->input('photo');
            $user->fullname = $request->input('nom') . ' ' . $request->input('prenom');
            $user->nom = $request->input('nom');
            $user->prenom = $request->input('prenom');
            $user->photo = $photourl;
            $user->save();
            // Find demande
            $demande->nom = $request->input('nom');
            $demande->prenom = $request->input('prenom');
            $demande->adresse = $request->input('adresse');
            $demande->adresse2 = $request->input('adresse2');
            $demande->cite = $request->input('cite');
            $demande->codepostal = $request->input('codepostal');
            $demande->country = $request->input('country');
            $demande->etablisement = $request->input('etablisement');
            $demande->photo =  $photourl;
            $demande->telephone = $request->input('telephone');
            $demande->typeecole = $request->input('typeecole');
            $demande->ville = $request->input('ville');
            $demande->contact_email = $request->input('contact_email');
            $demande->contact_phone = $request->input('contact_phone');

            $demande->save();

            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(404, ["Une erreur s'est produite lors de la mise à jour"], 'quelque chose s\'est mal passé');


        }
    }

    public function updateEmail(Request $request, $id)
    {
        try {
            // Retrieve the authenticated user
            $demande = DemandeInscription::findOrFail($id);
            if (!$demande) {

                return $this->errorResponse(422, null, ' demande n\'existe pas');
            }
            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if the authenticated user has permission to update the profile
            if ($user->id !==  intval($demande->user_id)) {
                return $this->errorResponse(401, ["Non autorisé"], 'quelque chose s\'est mal passé');

            }

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $demande->user_id,
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
            $demande->email = $request->input('email');
            $demande->save();
            // Update the email
            $user->email = $request->input('email');
            $user->save();

            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500,  ["Une erreur s'est produite lors de la mise à jour"], "Une erreur s'est produite lors de la mise à jour");
        }
    }
    public function updatePassword(Request $request, $id)
    {
        try {
            // Retrieve the authenticated user
            $demande = DemandeInscription::findOrFail($id);
            if (!$demande) {

                return $this->errorResponse(422, null, ' demande n\'existe pas');
            }
            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if the authenticated user has permission to update the profile
            if ($user->id !==  intval($demande->user_id)) {
                return $this->errorResponse(401, ["Non autorisé"], 'quelque chose s\'est mal passé');

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
            ];
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.passwordupdated',  ["data"=>$body], function($message) use( $body)
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


}
