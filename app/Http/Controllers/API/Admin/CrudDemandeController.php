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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class CrudDemandeController extends Controller
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
        $demandes = DemandeInscription::orderBy('created_at', 'desc')->get();
        return $this->successResponse($demandes, "Enregistrement effectué avec succès!", 201);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $demande = DemandeInscription::find($id);

        if (!$demande) {

            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($demande, "Enregistrement effectué avec succès!", 201);

    }


    public function update(Request $request,$id)
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
            $demande = DemandeInscription::findOrFail($id);
            if (!$demande) {
                return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');
            }
            $photourl = "/uploads/default/".$request->input('photo');
            if($demande->user_id){
                $user = User::findOrFail($id);
                $user->fullname = $request->input('nom') . ' ' . $request->input('prenom');
                $user->nom = $request->input('nom');
                $user->prenom = $request->input('prenom');
                $user->photo = $photourl;
                $user->save();
            }
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

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $rules = [
            'email'    => 'required|email|unique:demande_inscriptions',
            'telephone' =>'required',
            // 'photo' => 'required',
            'country' => 'required',
            'typeecole' => 'required',
            'nom' => 'required',
            'prenom' => 'required',
            'adresse' =>'required',
            'ville' => 'required',
            'cite' =>'required',
            'codepostal' => 'required',
            'etablisement' => 'required',

        ];
        $niceNames = array(
            'typeecole' => 'Votre Type Établissement',
            'country' => 'pays',
            'codepostal' => 'Code postal',
            'etablisement' => 'Votre Établissement',
            'prenom' => 'prénom',


        );
        $messages = array(
            'email.required' => 'Veuillez nous indiquer votre adresse email!',
            'email_email' => 'Veuillez fournir une adresse valide!',
            'telephone.required' =>'Veuillez nous indiquer votre numéro de téléphone',
            'country.required' => 'Veuillez fournir votre pays',
            'typeecole.required' => 'Veuillez fournir votre',
            'nom.required' => 'Veuillez fournir votre nom',
            'prenom.required' => 'Veuillez fournir votre prénom',
            'adresse.required' =>'Veuillez fournir votre adresse',
            'ville.required' => 'Veuillez fournir votre nom de ville',
            'cite.required' =>'Veuillez fournir cite',
            'codepostal.required' => 'Veuillez fournir votre code postal',
            'etablisement.required' => 'Veuillez fournir votre Établissement',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if ($request->filled('adresse2')) {
            $adresse2 = $request->adresse2;
        }
        else{
            $adresse2 = "";
        }
        $files = $request->file('file');
        $demande = DemandeInscription::create([
            'telephone' => $request->telephone,
            'email' => $request->email,
            'photo' => "/uploads/default/blank.png",
            'country' => $request->country,
            'typeecole' => $request->typeecole,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->adresse,
            'adresse2' => $adresse2,
            'ville' => $request->ville,
            'cite' => $request->cite,
            'codepostal' => $request->codepostal,
            'etablisement' => $request->etablisement,
        ]);
        $demande->save();
        if (is_array($files)) {
            foreach ($files as $index => $file) {
                if (!empty($file) && in_array($file->getClientOriginalExtension(), ['jpg', 'png','jpeg', 'pdf', 'zip', 'docx', 'doc', 'pptx', 'ppt'])) {
                    $path = '/uploads/' . Storage::disk('uploads')->put('/' . $request->user()->email . '/photos/' , $file);

                    // Fill 'copie1', 'copie2', 'copie3', 'copie4' fields in the loop
                    switch ($index) {
                        case 0:
                            $demande->update(['copie1' => $path]);
                            break;
                        case 1:
                            $demande->update(['copie2' => $path]);
                            break;
                        case 2:
                            $demande->update(['copie3' => $path]);
                            break;
                        case 3:
                            $demande->update(['copie4' => $path]);
                            break;
                    }
                }
            }
        }

        $body = [
            'name'=>$request->prenom,
            'email' =>$request->email,
        ];
//
//        Mail::to('email@email.com')->send(new BacancyMail($body));
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.processed',  ["data"=>$body], function($message) use($body)
        {
            $message
                ->from('email@email.com','Company')
                ->to( $body['email'], $body['name'])
                ->subject('Inscription réussie!');
        });
        return $this->successResponse(null ,"Enregistrement effectué avec succès!",201);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        try {



            $demande = DemandeInscription::findOrFail($id);
            if (!$demande) {
                return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');
            }
            if($demande->user_id){
                $user = User::findOrFail($demande->user_id);
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
                $user->delete();
            }


                $demande->delete();

            // For 'App\Models\Project', you may delete them or take another action, as needed.


            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultiple(Request $request)
    {
        $userIdsToDelete = $request->input('user_ids');
        $explode_id = array_map('intval', explode(',', $request->input('user_ids')));
        if (empty($userIdsToDelete)) {
            return $this->errorResponse(400, ['ID étudiants non valides fournis.'], 'quelque chose s\'est mal passé');
        }

        try {

            foreach ($explode_id as $userId) {

                $demande = DemandeInscription::findOrFail($userId);
                if (!$demande) {
                    return $this->errorResponse(404, ["demande n\'existe pas"], 'quelque chose s\'est mal passé');
                }


                if($demande->user_id){
                    $user = User::findOrFail($demande->user_id);
                    // Handle the relationships and related models.
                    $user->teams()->detach(); // Detach the user from all teams they belong to.

                    $ownedTeams = $user->ownedTeams;
                    foreach ($ownedTeams as $ownedTeam) {
                        $ownedTeam->delete();
                    }
                    DB::table('oauth_access_tokens')
                        ->where('user_id', $user->id)
                        ->update([
                            'revoked' => true
                    ]);
                    $user->delete();
                }





                    $demande->delete();


                // For 'App\Models\Project', you may delete them or take another action, as needed.


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
    public function acceptDemande($id, Request $request)
    {
        $Demande = DemandeInscription::find($id);
        if (!$Demande) {
            return $this->errorResponse(422, ["La demande n\'existe pas"], "Une erreur s'est produite lors de la mise à jour");
        }

        $password = Str::random(10);

        $whdata = [
            'username' => $this->username_client,
            'password' =>  $this->password_client,
            'accesskey' =>  $this->accesskey,
            'action' => 'AddUser',
            'firstname' => $Demande->prenom,
            'lastname' => $Demande->nom,
            'email' => $Demande->email,
            'password2' =>  $password,
            'responsetype' => 'json',
        ];
        $response = $this->AdminConnectWH($request, $whdata);

        if ($response['result'] === 'success') {
            $whdata2 = [
                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'action' => 'AddClient',
                'firstname' => $Demande->prenom,
                'lastname' => $Demande->nom,
                'email' =>  $Demande->email,
                'address1' =>  $Demande->adresse,
                'city' => $Demande->ville,
                'state' => $Demande->province,
                'postcode' => $Demande->codepostal,
                'country' => $Demande->country_code,
                'phonenumber' =>  $Demande->tel_format_national,
                'address2'=>  $Demande->adresse2,
                'owner_user_id'=>  $response['user_id'],
                'password2' => $password,
                'responsetype' => 'json',
            ];
            $response2 = $this->AdminConnectWH($request, $whdata2);

            if ($response2['result'] === 'success') {
                $user = new User();
                $user->password = bcrypt($password);
                $user->email = $Demande->email;
                $user->fullname = $Demande->prenom . ' ' . $Demande->nom;
                $user->prenom = $Demande->prenom;
                $user->nom = $Demande->nom;
                $user->role = 'etudiant';
                $user->wh_id = $response2['clientid'];
                $user->photo = $Demande->photo;
                $user->save();
                $insertedId = $user->id;

                $Demande2 = DemandeInscription::find($id);
                $Demande2->user_id = $insertedId;
                $Demande2->user_type = 'App\Models\User';
                $Demande2->demande_status = 'Validé';
                $Demande2->save();

                $body = [
                    'email' => $Demande->email,
                    'name' => $Demande->prenom,
                    'password' => $password,
                    'content' => "Toutes nos félicitations! Cette lettre a pour but de vous informer de l'acceptation de votre candidature.",
                    'title' => $Demande->demande_status,
                    'emailTitle' => $Demande->demande_status
                ];

                $beautymail = app()->make(Beautymail::class);
                $beautymail->send('emails.welcome',  ["data" => $body], function ($message) use ($body) {
                    $message
                        ->from('email@email.com', 'Company')
                        ->to($body['email'], $body['name'])
                        ->subject('Candidature acceptée!');
                });

                return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
            } else {
                return $this->errorResponse(422, ["Quelque chose s\'est mal passé lors de l\'ajout du client"], "Une erreur s'est produite lors de la mise à jour");

            }
        } else {
            return $this->errorResponse(422, ["Quelque chose s\'est mal passé lors de l\'ajout de l\'utilisateur"], "Une erreur s'est produite lors de la mise à jour");
        }
    }

    public function rejectDemande($id)
    {

        $Demande = DemandeInscription::find($id);
        if (!$Demande) {
            return $this->errorResponse(422, ["La demande n\'existe pas"], "Une erreur s'est produite lors de la mise à jour");
        }

        $Demande->demande_status = 'Refusé';
        $Demande->save();

        $body = [
            'email' => $Demande->email,
            'name'=>$Demande->prenom,

            'content'=>"Cette lettre a pour but de vous informer de Réjection de votre candidature.",
            'title'=>$Demande->demande_status,
            'emailTitle'=>$Demande->demande_status
        ];
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.rejected',  ["data"=>$body], function($message) use($body)
        {
            $message
                ->from('email@email.com','Company')
                ->to( $body['email'], $body['name'])
                ->subject('Candidature refusée!');
        });
        return $this->successResponse(null, "Enregistrement effectué avec succès!",201);



    }
}
