<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Snowfire\Beautymail\Beautymail;

class  CrudStudentController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        // Retrieve all students
        $users = User::where('role', "etudiant")        ->orderBy('created_at', 'desc')->get();
        return $this->successResponse($users, "Enregistrement effectué avec succès!", 201);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $etudiant = User::where('role', 'etudiant')->with('demande')->find($id);

        if (!$etudiant) {

            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($etudiant, "Enregistrement effectué avec succès!", 201);

    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteStudent($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {

                return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
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

            $demande = $user->demande;
            if ($demande) {
                $demande->delete();
            }

            // For 'App\Models\Project', you may delete them or take another action, as needed.

            $user->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultipleStudents(Request $request)
    {
        $userIdsToDelete = $request->input('user_ids');
        $explode_id = array_map('intval', explode(',', $request->input('user_ids')));
        if (empty($userIdsToDelete)) {
            return $this->errorResponse(400, ['ID étudiants non valides fournis.'], 'quelque chose s\'est mal passé');
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

                $demande = $user->demande;
                if ($demande) {
                    $demande->delete();
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
            return $this->errorResponse(404, ['Un ou plusieurs étudiants introuvables.'], 'quelque chose s\'est mal passé');

        }
    }

    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStudent(Request $request,$id)
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
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["etudiant n\'existe pas"], 'quelque chose s\'est mal passé');
            }

            $demande = DemandeInscription::where('user_id',$user->id)->first();
            if (!$demande) {
                return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');
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
            return $this->errorResponse(500, $e, "Une erreur s'est produite lors de la mise à jour");

        }
    }

    public function updateStudentEmail(Request $request, $id)
    {
        try {
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["etudiant n\'existe pas"], 'quelque chose s\'est mal passé');
            }
            $demande = DemandeInscription::where('user_id',$user->id)->first();
            if (!$demande) {
                return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');
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
                    ->from('email@email.com','Company')
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
    public function updateStudentPassword(Request $request, $id)
    {
        try {
            // Retrieve user
            $user = User::findOrFail($id);
            if (!$user) {
                return $this->errorResponse(404, ["etudiant n'existe pas"], 'quelque chose s\'est mal passé');
            }
            $demande = DemandeInscription::where('user_id',$user->id)->first();
            if (!$demande) {
                return $this->errorResponse(404, ["demande n'existe pas"], 'quelque chose s\'est mal passé');
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
                    ->from('email@email.com','Company')
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
