<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class AdministrateurController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admins = User::where('role','=' ,'zen_monitor')

            ->get();
        return $this->successResponse($admins,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(Request $request)
    {
        $data = $request->all();


        $rules = [
            'email'    => 'required|email|unique:users',


            'nom' => 'required',
            'prenom' => 'required',

        ];
        $niceNames = array(

            'prenom' => 'prénom',


        );
        $messages = array(

            'email.required' => 'Veuillez nous indiquer votre adresse email!',
            'email_email' => 'Veuillez fournir une adresse valide!',

            'nom.required' => 'Veuillez fournir votre nom',
            'prenom.required' => 'Veuillez fournir votre prénom',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        $password =  Str::random(10);
        $user = new User();
        $user->email = $request->email;
        $user->fullname = $request->prenom.' '.$request->nom;
        $user->prenom = $request->prenom;
        $user->nom = $request->nom;
        $user->password = bcrypt($password);

        $user->role = 'monitor';


        $user->save();
      $content =  'Email:'.$request->email.'Mot de passe:'.$password;
        $body = [
            'name'=>$request->prenom,
            'email' =>$request->email,
            'password' =>$password,
            'content'=>$content,
            'title'=>'Compte créé',
            'emailTitle'=>'Compte administrateur'
        ];
//
//        Mail::to('email@email.com')->send(new BacancyMail($body));
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.custom',  ["data"=>$body], function($message) use($body)
        {
            $message
                ->from('email@email.com','Company')
                ->to( $body['email'], $body['name'])
                ->subject('Compte administrateur créé!');
        });
        return $this->successResponse($user,'Enregistrement effectué avec succès!', 201);

    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function edit(Request $request,$id)
    {
        $data = $request->all();
        $user = User::find($id);
        if(!$user){
            $response = ["message" =>"Administrateur n'existe pas"];
            return response($response, 422);
        }
        $rules = [
            'email'    => 'required|email|unique:users,email,'.$user->id.',id',


            'nom' => 'required',
            'prenom' => 'required',

        ];
        $niceNames = array(

            'prenom' => 'prénom',


        );
        $messages = array(

            'email.required' => 'Veuillez nous indiquer votre adresse email!',
            'email_email' => 'Veuillez fournir une adresse valide!',

            'nom.required' => 'Veuillez fournir votre nom',
            'prenom.required' => 'Veuillez fournir votre prénom',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        $user = User::find($id);
        if(!$user){
            return $this->errorResponse( 422,null, 'Administrateur n\'existe pas' );

        }

        $user->email = $request->email;

        $user->nom = $request->nom;
        $user->prenom = $request->prenom;
        if($request->password != ''){
            $user->password =   bcrypt($request->password);
        }




        $user->save();
        $body = [
            'name'=>'Riadh',
            'content'=>'',
                'title'=>$request->demande_status,
            'emailTitle'=>$request->demande_status
        ];
//
//        Mail::to('email@email.com')->send(new BacancyMail($body));
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.custom',  ["data"=>$body], function($message) use($body)
        {
            $message
                ->from('email@email.com','Company')
                ->to('email@email.com', 'John Smith')
                ->subject('Mise a jour réussie!');
        });

        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
    }
    /**
     * Display the specified resource.
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function show( $id)
    {
        $user = User::find($id)   ;
        if (!$user){
            return $this->errorResponse( 422,null, 'Administrateur n\'existe pas' );
        }
        return $this->successResponse($user,'Enregistrement effectué avec succès!', 201);

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if(!$user) {
            return $this->errorResponse( 422,null, 'Administrateur n\'existe pas' );
        }
            $user->token()->revoke();
            $user->delete();


        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
    }
}
