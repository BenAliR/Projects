<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DemandeResource;
use App\Mail\BacancyMail;
use App\Models\DemandeInscription;
use App\Models\Project;
use App\Models\Projet;
use App\Models\sharedData;
use App\Models\Team;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailTemplate;
use App\Models\DemandeEmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class DemandeInscriptionController extends Controller
{
    use ApiResponser;
    public $username_client = 'grapYDri06nLT1iM8msLSOCijYBxyyA3';
    public $password_client = '1ygwi3aBAsyuDGYKxV1r0rBgfnXw4aBZ';
    public $accesskey = 'zenLSbUtPDMpw9b7a6zu67h5n8ksp82Cqf62utYHSpxfqeducation';
    public $url = 'https://c.education.zenhosting.tn/includes/api.php';
    /**
     * Connect to WH.
     *
     * @param $whdata
     * @param Request $request
     * @return \Illuminate\Http\Client\Response
     */
    public function AdminConnectWH(Request $request,$whdata)
    {




        $response = Http::get($this->url, $whdata);
        return $response;




    }

    /**
     * Show the form for creating a new resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function AcceptOrder($id)
    {
        $demande = DemandeInscription::with('DemandeEmails','DemandeProjects','user','user.teams','user.ownedTeams','DemandeProjects.ProjectTeam')->find($id);
        if (!$demande){

            return $this->errorResponse( 422,null, ' demande n\'existe pas' );
        }
        return $this->successResponse($demande ,"Enregistrement effectué avec succès!",201);
    }


    public function bannedStatus()
    {
        $user_id = 1;
        $user = User::find($user_id);

        $message = "The user is not banned";
        if ($user->banned != null) {
            if ($user->banned == 0) {
                $message = "Banned Permanently";
            }

            if (now()->lessThan($user->banned)) {
                $banned_days = now()->diffInDays($user->banned) + 1;
                $message = "Suspended for " . $banned_days . ' ' . Str::plural('day', $banned_days);
            }
        }

        dd($message);
    }
    public function unban($id)
    {
        $user_id = $id;
        $user = User::find($user_id);
        if(!$user)
        {

            return $this->errorResponse(422,null,'utilisateur n\'existe pas' );


        }
        $user->banned = null;
        $user->save();
        return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ban($id)
    {
        // ban for days
        $ban_for_next_7_days = Carbon::now()->addDays(7);
        $ban_for_next_14_days = Carbon::now()->addDays(14);
        $ban_permanently = 0;

        // ban user
        $user_id = $id;
        $user = User::find($user_id);
        if(!$user)
        {

            return $this->errorResponse(422,null,'utilisateur n\'existe pas' );


        }
        $user->banned = $ban_permanently;
        $user->save();
        return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

    }
    /**
     * Update the specified resource in storage.
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function AcceptDemande($id,Request $request)
    {

        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            return $this->errorResponse( 422,null, 'demande n\'existe pas' );

        }
        $password =  Str::random(10);


        $whdata =   [

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
        $response = $this->AdminConnectWH($request,$whdata);

        if($response['result'] === 'success'){
            $whdata2 =   [

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
            $response2 = $this->AdminConnectWH($request,$whdata2);

            if($response2['result'] === 'success'){
               /// Create User

                $user = new User();
                $user->password = bcrypt($password);
                $user->email = $Demande->email;
                $user->fullname = $Demande->prenom.' '.$Demande->nom;
                $user->prenom = $Demande->prenom;
                $user->nom = $Demande->nom;
                $user->role = 'etudiant';
                 $user->wh_id = $response2['clientid'];
                $user->photo = $Demande->photo;
                $user->save();
                $insertedId = $user->id;
                $Demande2 = DemandeInscription::find($id);
                $Demande2->user_id =$insertedId;
                $Demande2->user_type = 'App\Models\User';
                $Demande2->demande_status = 'Validé';
                $Demande2->save();

                $body = [
                    'email' => $Demande->email,
                    'name'=>$Demande->prenom,
                    'password'=>$password,
                    'content'=>"Toutes nos félicitations! Cette lettre a pour but de vous informer de l'acceptation de votre candidature.",
                    'title'=>$Demande->demande_status,
                    'emailTitle'=>$Demande->demande_status
                ];
                $beautymail = app()->make(Beautymail::class);
                $beautymail->send('emails.welcome',  ["data"=>$body], function($message) use($body)
                {
                    $message
                        ->from('riadh@zenhosting.pro','Zenhosting')
                        ->to( $body['email'], $body['name'])
                        ->subject('Candidature acceptée!');
                });
                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
            }else{
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }

        }else{
            return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

        }


    }





    /**
     * Update the specified resource in storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function RejectDemande($id)
    {

        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            return $this->errorResponse( 422,null, ' demande n\'existe pas' );

        }

                /// Refuse demande


                $Demande2 = DemandeInscription::find($id);


                $Demande2->demande_status = 'Refusé';
                $Demande2->save();

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
                        ->from('riadh@zenhosting.pro','Zenhosting')
                        ->to( $body['email'], $body['name'])
                        ->subject('Candidature refusée!');
                });
                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);



    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function UserTeams($id)
    {
        $demande = DemandeInscription::with('DemandeEmails','DemandeProjects')->find($id);
        if (!$demande){

            return $this->errorResponse( 422,null, ' demande n\'existe pas' );
        }
        return $this->successResponse($demande ,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Demandes = DemandeInscription::orderBy('created_at','desc')->get();


        return $this->successResponse($Demandes ,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

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
             'etablisement.required' => 'Veuillez fournir votre Établissement',
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
        $demande =    DemandeInscription::create(      [

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
                    $path = '/uploads/' . Storage::disk('uploads')->put('/' . $request->email . '/photos/' , $file);

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
//        Mail::to('riadh@zenhosting.pro')->send(new BacancyMail($body));
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.processed',  ["data"=>$body], function($message) use($body)
        {
            $message
                ->from('riadh@zenhosting.pro','Zenhosting')
                ->to( $body['email'], $body['name'])
                ->subject('Inscription réussie!');
        });
        return $this->successResponse(null ,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function show( $id)
    {
        $demande = DemandeInscription::with('DemandeEmails','DemandeProjects','user','user.teams','user.ownedTeams','DemandeProjects.ProjectTeam')->find($id);
        if (!$demande){

            return $this->errorResponse( 422,null, ' demande n\'existe pas' );
        }
        return $this->successResponse($demande ,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {
        $data = $request->all();
        $rules = [
            'email'    => 'required|email',
            'telephone' =>'required',
            'country' => 'required',
            'nom' => 'required',
            'prenom' => 'required',
            'adresse' =>'required',
            'adresse2' =>'required',
            'ville' => 'required',
            'province' =>'required',
            'codepostal' => 'required',

        ];
        $niceNames = array(
            'country' => 'pays',
            'codepostal' => 'Code postal',
            'prenom' => 'prénom',


        );
        $messages = array(
            'email.required' => 'Veuillez nous indiquer votre adresse email!',
            'email_email' => 'Veuillez fournir une adresse valide!',
            'telephone.required' =>'Veuillez nous indiquer votre numéro de téléphone',
            'country.required' => 'Veuillez fournir votre pays',
            'nom.required' => 'Veuillez fournir votre nom',
            'prenom.required' => 'Veuillez fournir votre prénom',
            'adresse.required' =>'Veuillez fournir votre adresse',
            'adresse2.required' =>'Veuillez fournir votre adresse 2',
            'ville.required' => 'Veuillez fournir votre nom de ville',
            'province.required' =>'Veuillez fournir la province',
            'codepostal.required' => 'Veuillez fournir votre code postal',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);

        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            return $this->errorResponse( 422,null, "Demande n'existe pas" );
        }
        $Demande->telephone = $request->telephone;
            $Demande->email = $request->email;
            $Demande->country = $request->country;
        $Demande->tel_format_national = $request->tel_format_national;
        $Demande->country_code = $request->country_code;
            $Demande->nom = $request->nom;
            $Demande->prenom = $request->prenom;
            $Demande->adresse = $request->adresse;
            $Demande->adresse2 = $request->adresse2;
            $Demande->ville = $request->ville;
            $Demande->province= $request->province;
            $Demande->codepostal = $request->codepostal;
        $Demande->save();

        if($Demande->demande_status === 'Validé'){
            $user = User::find($Demande->user_id);
            if(!$user){
                return $this->errorResponse( 422,null, "utilisateur n'existe pas" );
            }




                $whdata2 =   [

                    'username' => $this->username_client,
                    'password' =>  $this->password_client,
                    'clientid' =>  $user->wh_id,
                    'accesskey' =>  $this->accesskey,
                    'action' => 'UpdateClient',
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
                    'responsetype' => 'json',


                ];
                $response = $this->AdminConnectWH($request,$whdata2);
                if($response['result'] === 'success'){


                    $user->email = $Demande->email;
                    $user->fullname = $Demande->prenom.' '.$Demande->nom;
                    $user->prenom = $Demande->prenom;
                    $user->nom = $Demande->nom;
                    $user->role = 'etudiant';
                    $user->wh_id = $response['clientid'];
                    $user->photo = $Demande->photo;
                    $user->save();

                }
                else{
                    return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
                }







        }
        return $this->successResponse($Demande ,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function EditUniversityInfo(Request $request,$id)
    {
        $data = $request->all();
        $rules = [
            'typeecole' => 'required',
            'etablisement' => 'required',
        ];
        $niceNames = array(
            'typeecole' => 'Votre Type Établissement',
            'etablisement' => 'Votre Établissement',
        );
        $messages = array(
            'etablisement.required' => 'Veuillez fournir votre Établissement',
            'typeecole.required' => 'Veuillez fournir votre',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);

        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );

        }

        if(!empty($request->deletedpicture)){
            $imagetoremove = explode(',', $request->deletedpicture);
        }
        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            return $this->errorResponse( 422,null, "Demande n'existe pas" );

        }
        if(!empty($request->deletedpicture)){
            foreach ($imagetoremove as $img){
                $i =  str_replace('/uploads', '', $Demande->$img);
                $i2 =  str_replace('//', '/', $i);


                Storage::disk('uploads')->delete($i2);

            }
        }

        $Demande->typeecole = $request->typeecole;
        if(!empty($request->deletedpicture)){
            for ($i = 0; $i < count($imagetoremove); $i++) {

                $Demande[$imagetoremove[$i]] = '/uploads/' .Storage::disk('uploads')->put('/'.$data['email'].'/photos/', $data['files'][$i]);

            }}
        $Demande->etablisement = $request->etablisement;
        $Demande->save();
        return $this->successResponse($Demande ,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function AccpetProject(Request $request,$id)
    {
        $project = Project::where('id','=',$id)->with('ProjectTeam','ProjectTeam.TeamOwner:id,wh_id')->first();


        if($project) {
 $idWH = $project->ProjectTeam->TeamOwner->wh_id;
            $whdata =  [

                'action' => 'AddOrder',
                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'clientid' =>  $project->ProjectTeam->TeamOwner->wh_id,
                'accesskey' =>  $this->accesskey,
                'pid' => 1,
                'domain' => $project->domaine,
                'paymentmethod' => 'mailin',
                'dnsmanagement' => 1,
                'nameserver1' => 'dns1.zenhosting.info',
                'nameserver2' => 'dns2.zenhosting.info',
                'nameserver3' => 'dns3.zenhosting.info',
                'nameserver4' => 'dns4.zenhosting.info',

                'responsetype' => 'json',


            ];
            $response = $this->AdminConnectWH($request,$whdata);
            if($response['result'] === 'success') {
                $whdata2 =  [

                    'action' => 'AcceptOrder',
                    'username' => $this->username_client,
                    'password' =>  $this->password_client,

                    'accesskey' =>  $this->accesskey,
                    'orderid' => $response['orderid'],
//                    'registrar' => 'enom',
                       // 'autosetup' => true,
                   // 'sendemail' => true,
                    'responsetype' => 'json',


                ];
                $response2 = $this->AdminConnectWH($request,$whdata2);
                if($response2['result'] === 'success') {
                    $project->project_status  ='Active';
                    $project->serviceid  = $response['serviceids'];

                    $project->save();


                    sharedData::updateOrCreate(
                        [
                            'project_id'  =>      $project->id,
                            'key'=> $project->ProjectTeam->TeamOwner->wh_id,


                        ],[
                            'token'=> 'test',
                            'status'=> 'test',
                            'url'=> 'test',
                        ]
                    );
                    return $this->successResponse($project,"Enregistrement effectué avec succès!",201);

                }else{
                    return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

                }

            }else{
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }




        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }

    }


    /**
 * Update the specified resource in storage.
 *
 * @param Request $request
 * @param $id
 * @return \Illuminate\Http\Response
 */
    public function SuspendProject(Request $request,$id)
    {
        $project = Project::where('id','=',$id)->first();


        if($project) {

            $whdata =  [

                'action' => 'ModuleSuspend',
                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'serviceid' => $project->serviceid,
                'suspendreason' => 'Abuse',
                'responsetype' => 'json',


            ];
            $response = $this->AdminConnectWH($request,$whdata);
            if($response['result'] === 'success')
            {
                $project->project_status  ='Suspendu';
                $project->save();

                return $this->successResponse($project, "Enregistrement effectué avec succès!", 201);
            }
            else
            {
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }


        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }

    }
    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function UnSuspendProject(Request $request,$id)
    {
        $project = Project::where('id','=',$id)->first();


        if($project) {

            $whdata =  [

                'action' => 'ModuleUnsuspend',
                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'serviceid' => $project->serviceid,
                'responsetype' => 'json',


            ];
            $response = $this->AdminConnectWH($request,$whdata);
            if($response['result'] === 'success')
            {
                $project->project_status  ='Active';
                $project->save();

                return $this->successResponse($project, "Enregistrement effectué avec succès!", 201);
            }
            else
            {
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }


        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function TerminateProject(Request $request,$id)
    {
        $project = Project::where('id','=',$id)->first();


        if($project) {

            $whdata =  [

                'action' => 'ModuleTerminate',
                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'serviceid' => $project->serviceid,
                'responsetype' => 'json',


            ];
            $response = $this->AdminConnectWH($request,$whdata);
            if($response['result'] === 'success')
            {
                $project->project_status  ='Désactivé';
                $project->save();

                return $this->successResponse($project, "Enregistrement effectué avec succès!", 201);
            }
            else
            {
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }


        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function UpdateProject(Request $request,$id)
    {
        $data = $request->all();
        $rules = [

            'titre_projet' => 'required',
            'description' => 'required',
            'type_projet' => 'required',
            'file' => 'required',
            'dev_technologie' => 'required',

            'static_image' => 'required',
        ];
        $niceNames = array(

            'file' => 'Avatar de projet',
            'titre_projet' => 'Titre du projet',
            'type_projet' => 'Type du projet',
            'nom_equipe' => 'Nom d\'équipe ',
            'static_image' => 'random avatar',
            'dev_technologie' => 'un framework ou une bibliothèque',

        );
        $messages = array(

            'domaine.required' => 'Veuillez fournir votre Nom domaine',
            'nom_equipe.required' => 'Veuillez fournir votre Nom d\'équipe',

            'static_image.required' => 'Veuillez fournir id avatar de [1,12] sinon null',
            'file.required' => 'Veuillez fournir votre photo du projet',
            'titre_projet.required' => 'Veuillez fournir votre Titre du projet',
            'dev_technologie.required' => 'Veuillez fournir au moins un framework ou une bibliothèque',
            'description.required' => 'Veuillez fournir votre Description du projet',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        $project = Project::where('id','=',$id)->with('ProjectTeam')->first();
        if($project) {



                if($request->static_image === 'null' && $request->file !== 'null' ){
                    if($data['file']){
                        $imagepath = Storage::disk('uploads')->put('/'.$request->user()->email.'/projets/'.$project->slug, $data['file']);
                        $img = '/uploads/' . $imagepath;
                    }else{
                        $img = '/uploads/ProjectAvatar/1.png';
                    }


                }
                elseif ($request->static_image !== 'null'){
                    $img = '/uploads/ProjectAvatar/' . $request->static_image.'.png';
                }
                else{
                    $img = $project->project_avatar;
                }

                $project->title  = $request->titre_projet;
                $project->dev_technologie  = $request->dev_technologie;
                $project->description = $request->description;
                //   'slug' =>  $slug,
                $project->project_avatar = $img;
                $project->type = $request->type_projet;
                $project->save();

                $newPost = $project->replicate();

                $team = Team::where('id','=',$project->ProjectTeam->id)->first();
                $team->name =  $request->nom_equipe;
                $team->save();
                return $this->successResponse(null,"mise à jour du projet réussie",201);
        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }





    }

    /**
     * Update the specified resource in storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function updateOld($id)
    {

        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            $response = ["message" =>"Demande n'existe pas"];
            return response($response, 422);
        }
        $username_client = 'grapYDri06nLT1iM8msLSOCijYBxyyA3';
        $password_client = '1ygwi3aBAsyuDGYKxV1r0rBgfnXw4aBZ';
        $accesskey = 'zenLSbUtPDMpw9b7a6zu67h5n8ksp82Cqf62utYHSpxfqeducation';
        $password =  Str::random(10);
        $response = Http::get('https://c.education.zenhosting.tn/includes/api.php', [


            'username' => $username_client,
            'password' => $password_client,
            'accesskey' => $accesskey,
            'action' => 'AddUser',
            'firstname' => $Demande->prenom,
            'lastname' => $Demande->nom,
            'email' => $Demande->email,
            'password2' =>  $password,
            'responsetype' => 'json',


        ]);
        if($response){
            $response2 = Http::get('https://c.education.zenhosting.tn/includes/api.php', [

                'action' => 'AddClient',
                'username' => $username_client,
                'password' => $password_client,
                'accesskey' => $accesskey,
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


            ]);

            if($response2){
                $response3 = Http::get('https://c.education.zenhosting.tn/includes/api.php', [

                    'action' => 'AddOrder',
                    'username' => $username_client,
                    'password' => $password_client,
                    'accesskey' => $accesskey,
                    'clientid' =>$response2['clientid'],
                    'pid' => 1,
                    'domain' => $Demande->domaine,
                    'paymentmethod' => 'mailin',
                    'dnsmanagement' => 1,
                    'nameserver1' => 'dns1.zenhosting.info',
                    'nameserver2' => 'dns2.zenhosting.info',
                    'nameserver3' => 'dns3.zenhosting.info',
                    'nameserver4' => 'dns4.zenhosting.info',

                    'responsetype' => 'json',


                ]);
                return $response3;
            }

        }


    }
    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $Demande = DemandeInscription::find($id);

        if(!$Demande) {
            return $this->errorResponse( 422,null, ' demande n\'existe pas' );
        }

        $email = $Demande->email;
        Storage::disk('uploads')->deleteDirectory($email);
        $user = User::where('email', '=', $email)->first();
        $Demande->delete();
        if($user) {
            $user->token()->revoke();
            $user->delete();
        }
        return $this->successResponse(null ,"Enregistrement effectué avec succès!",201);

    }

    /**
     *
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function sendEmail(Request $request, $id)
    {
        $Demande = DemandeInscription::find($id);
        if(!$Demande){
            $response = ["message" =>"Demande n'existe pas"];
            return response($response, 422);
        }
        $content = $request->emailcontent;
        $subject = $request->emailsubject;
        $sujet = $request->emailsujet;
        $body = [
            'name'=> $Demande->prenom,
            'content'=>$content,
            'title'=>$subject,
            'emailTitle'=>$sujet
        ];
        $nom =  $Demande->nom .' '. $Demande->prenom;
        $email = $Demande->email;

        $beautymail = app()->make(Beautymail::class);
        if($request->template_save === 'true'){
            $EmailTemplate = EmailTemplate::create(      [

                'subject' => $request->emailsubject,
                'sujet' => $request->emailsujet,
                'emailcontent' => $request->emailcontent,

            ]);
        }
        $EmailDemande = DemandeEmail::create(      [

            'subject' => $request->emailsubject,
            'sujet' => $request->emailsujet,
            'emailcontent' => $request->emailcontent,
            'demande_id' => $id

        ]);
        if($request->emailtype === 'E-mail ordinaire' ){
            try{
            $beautymail->send('emails.custom',  ["data"=>$body], function($message) use($subject, $nom, $email, $body)
            {
                $message
                    ->from('riadh@zenhosting.pro','Zenhosting')
                    ->to('riadh@zenhosting.pro','Zenhosting')
                    ->subject($subject);
            });
            }

            catch(\Exception $e){

            }
        }
        if($request->emailtype === 'E-mail de bienvenue' ){
            try{
            $beautymail->send('emails.welcome',  ["data"=>$body], function($message) use($subject, $nom, $email, $body)
            {
                $message
                    ->from('riadh@zenhosting.pro','Zenhosting')
                    ->to('riadh@zenhosting.pro','Zenhosting')
                    ->subject($subject);
            });
        }

    catch(\Exception $e){

    }

        }
        if($request->emailtype === 'E-mail de vérification' ){
            try{
                $beautymail->send('emails.verified',  ["data"=>$body], function($message) use($subject, $nom, $email, $body)
                {
                    $message
                        ->from('riadh@zenhosting.pro','Zenhosting')
                        ->to('riadh@zenhosting.pro','Zenhosting')
                        ->subject($subject);
                });
            }

            catch(\Exception $e){

            }
        }
        if($request->emailtype === 'E-mail d’acceptation' ){
            try{
                $beautymail->send('emails.accepted',  ["data"=>$body], function($message) use($subject, $nom, $email, $body)
                {
                    $message
                        ->from('riadh@zenhosting.pro','Zenhosting')
                        ->to('riadh@zenhosting.pro','Zenhosting')
                        ->subject($subject);
                });
            }

            catch(\Exception $e){

            }
        }
        if($request->emailtype === 'E-mail de rejet' ){
            try{
                $beautymail->send('emails.rejected',  ["data"=>$body], function($message) use($subject, $nom, $email, $body)
                {
                    $message
                        ->from('riadh@zenhosting.pro','Zenhosting')
                        ->to('riadh@zenhosting.pro','Zenhosting')
                        ->subject($subject);
                });
            }

            catch(\Exception $e){

            }
        }

        return response([
            'statut'=>"Mise a jour réussie!",
            'status'         => 200,
            'demande' => !empty($request->deletedpicture)
        ]);
    }


}
