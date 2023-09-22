<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class CrudUsersAdminController extends Controller
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
        $users = User::where('role', "monitor")        ->orderBy('created_at', 'desc')->get();
        return $this->successResponse($users, "Enregistrement effectué avec succès!", 201);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $etudiant = User::where('role', 'monitor')->find($id);

        if (!$etudiant) {

            return $this->errorResponse(404, ['Adminstrateur introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($etudiant, "Enregistrement effectué avec succès!", 201);

    }
    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAdminEmail(Request $request)
    {
        try {

            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if the authenticated user has permission to update the profile

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . $user->id,
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
            // Update the email
            $user->email = $request->input('email');
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
     * @return JsonResponse
     */
    public function updateAdminPassword(Request $request)
    {
        try {

            // Retrieve the authenticated user
            $user = Auth::user();
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
    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAdmin(Request $request,$id)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string',
                'prenom' => 'required|string',

            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            // Retrieve user
            $user = User::findOrFail(intval($id));
            if (!$user) {
                return $this->errorResponse(404, ["admin n\'existe pas"], 'quelque chose s\'est mal passé');
            }

            $photourl = "/uploads/default/".$request->input('photo');
            $user->fullname = $request->input('nom') . ' ' . $request->input('prenom');
            $user->nom = $request->input('nom');
            $user->prenom = $request->input('prenom');
            $user->photo = $photourl;
            $user->save();


            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500, $e, "Une erreur s'est produite lors de la mise à jour");

        }
    }

    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
            public function store(Request $request)
            {

                $data = $request->all();

                $validator = Validator::make($data, [
                    'email' => 'required|email|unique:users',
                    'nom' => 'required',
                    'prenom' => 'required',
                ], [
                    'email.required' => 'Veuillez nous indiquer votre adresse email!',
                    'email.email' => 'Veuillez fournir une adresse valide!',
                    'nom.required' => 'Veuillez fournir votre nom',
                    'prenom.required' => 'Veuillez fournir votre prénom',
                ], [
                    'prenom' => 'prénom',
                ]);

                if ($validator->fails()) {
                    return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé');
                }

                $password = Str::random(10);
                $user = new User();
                $user->email = $request->email;
                $user->fullname = $request->prenom . ' ' . $request->nom;
                $user->prenom = $request->prenom;
                $user->nom = $request->nom;
                $user->password = bcrypt($password);
                $user->role = 'monitor';
                $user->save();

                $content = 'Email: ' . $request->email . ' Mot de passe: ' . $password;
                $body = [
                    'name' => $request->prenom,
                    'email' => $request->email,
                    'password' => $password,
                    'content' => $content,
                    'title' => 'Compte créé',
                    'emailTitle' => 'Compte administrateur'
                ];
                $beautymail = app()->make(Beautymail::class);
                $beautymail->send('emails.newaccount', ["data" => $body], function ($message) use ($body) {
                    $message
                        ->from('email@email.com', 'Company')
                        ->to($body['email'], $body['name'])
                        ->subject('Compte administrateur créé!');
                });

                return $this->successResponse($user, 'Enregistrement effectué avec succès!', 201);


            }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultipleAdmins(Request $request)
    {
        $userIdsToDelete = $request->input('user_ids');
        $explode_id = array_map('intval', explode(',', $request->input('user_ids')));
        if (empty($userIdsToDelete)) {
            return $this->errorResponse(400, ['ID admintrateurs non valides fournis.'], 'quelque chose s\'est mal passé');
        }

        try {

            foreach ($explode_id as $userId) {
                $user = User::findOrFail($userId);
                DB::table('oauth_access_tokens')
                    ->where('user_id', $user->id)
                    ->update([
                        'revoked' => true
                    ]);
                $user->delete();
            }
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Un ou plusieurs admintrateurs introuvables.'], 'quelque chose s\'est mal passé');

        }
    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteAdmin($id)
    {
        try {

            $user = User::find($id);

            if (!$user) {

                return $this->errorResponse(404, ['Adminstrateur introuvable'], 'quelque chose s\'est mal passé');
            }
            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->update([
                    'revoked' => true
                ]);
            $user->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Adminstrateur introuvable'], 'quelque chose s\'est mal passé');
        }
    }
}
