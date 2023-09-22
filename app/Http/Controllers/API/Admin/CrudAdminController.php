<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class CrudAdminController extends Controller
{
    use ApiResponser;
    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateEmail(Request $request)
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
    public function updatePassword(Request $request)
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
            ];
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.passwordupdated',  ["data"=>$body], function($message) use( $body)
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
    public function edit(Request $request)
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
            $demande = DemandeInscription::findOrFail($request->input('id'));
            if (!$demande) {
                return $this->errorResponse(404, ["demande introuvable"],"demande introuvable" );

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


            return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500, [], "Une erreur s'est produite lors de la mise à jour");

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
    { $data = $request->all();

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
        $user->role = 'zen_monitor';
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
}
