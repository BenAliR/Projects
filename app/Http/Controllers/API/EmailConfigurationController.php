<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailConfiguration;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailConfigurationController extends Controller
{
    use ApiResponser;
    public function createConfiguration(Request $request) {


        $data = $request->all();
        $rules = [

        'driver'=> 'required',
     'encryption'=> 'required',
     'host' => 'required',
     'password'=> 'required',
    'user_name'=> 'required',
      'port'=> 'required',
      'receiver_email'=> 'required',
       'sender_email'=> 'required',
     'sender_name'=> 'required',

        ];
        $niceNames = array(
            'driver'=> 'nom driver',
            'encryption'=> 'methode encryption',
            'host' => 'nom host',
            'password'=> 'password',
            'user_name'=> 'nom user',
            'port'=> 'port',
            'receiver_email'=> 'receiver email',
            'sender_email'=> 'sender email',
            'sender_name'=>'nom sender',

        );
        $messages = array(

            'driver'=> 'Veuillez fournir nom driver',
            'encryption'=> 'Veuillez fournir methode encryption',
            'host' => 'Veuillez fournir nom host',
            'password'=> 'Veuillez fournir password',
            'user_name'=> 'Veuillez fournir nom user',
            'port'=> 'Veuillez fournir port',
            'receiver_email'=> 'Veuillez fournir receiver email',
            'sender_email'=> 'Veuillez fournir sender email',
            'sender_name'=>'Veuillez fournir nom sender',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $contents_email = EmailConfiguration::take(1)->first();
        if($contents_email){

            $contents_email->driver= $request->driver;
            $contents_email->encryption= $request->encryption;
            $contents_email->host= $request->host;
            $contents_email->password= $request->password;
            $contents_email->user_name= $request->user_name;
            $contents_email->port= $request->port;
            $contents_email->receiver_email= $request->receiver_email;
            $contents_email->sender_email= $request->sender_email;
            $contents_email->sender_name= $request->sender_name;
            $contents_email->user_id= $request->user()->id;
            $contents_email->save();
            return $this->successResponse($contents_email ,"Enregistrement effectué avec succès!",201);


        }else{
            $configuration  =   EmailConfiguration::create([
                "user_id"       =>      $request->user()->id,
                "driver"        =>      $request->driver,
                "host"          =>      $request->host,
                "port"          =>      $request->port,
                "encryption"    =>      $request->encryption,
                "user_name"     =>      $request->user_name,
                "password"      =>      $request->password,
                "sender_name"   =>      $request->sender_email,
                "sender_email"  =>      $request->sender_name
            ]);


            return $this->successResponse($configuration ,"Enregistrement effectué avec succès!",201);


        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function Configuration()
    {
        $contents_arr = EmailConfiguration::take(1)->first();
        if($contents_arr) {
            return $this->successResponse($contents_arr ,"Enregistrement effectué avec succès!",201);


        }
        else {
            return $this->errorResponse(422, null,'Configuration de messagerie manquante' );
        }
    }
}
