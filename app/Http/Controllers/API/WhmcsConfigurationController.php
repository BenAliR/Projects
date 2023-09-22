<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WhmcsConfiguration;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhmcsConfigurationController extends Controller
{
    use ApiResponser;
    public function createConfiguration(Request $request) {


        $data = $request->all();
        $rules = [
            'wh_url' => 'required',
            'wh_accesskey' => 'required',
            'wh_password_client'=> 'required',
            'wh_username_client'=> 'required',

        ];
        $niceNames = array(
            'wh_url' => 'url whmcs',
            'wh_accesskey' => 'wh accesskey',
            'wh_password_client'=> 'wh password client',
            'wh_username_client'=> 'wh username client',

        );
        $messages = array(
            'wh_url' => 'Veuillez fournir nom d\'application',
            'wh_accesskey' => 'Veuillez fournir wh accesskey',
            'wh_password_client'=> 'Veuillez fournir wh password client',
            'wh_username_client'=> 'Veuillez fournir wh username client',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        $contents_wh = WhmcsConfiguration::take(1)->first();
        if($contents_wh){

            $contents_wh->wh_url= $request->wh_url;
            $contents_wh->wh_accesskey= $request->wh_accesskey;
            $contents_wh->wh_password_client= $request->wh_password_client;
            $contents_wh->wh_username_client= $request->wh_username_client;
            $contents_wh->save();
            return $this->successResponse($contents_wh ,"Enregistrement effectué avec succès!",201);


        }else{
            $configuration  =   WhmcsConfiguration::create([
                "wh_url"       =>      $request->wh_url,
                "wh_accesskey"        =>      $request->wh_accesskey,
                "wh_password_client"          =>      $request->wh_password_client,
                "wh_username_client"          =>      $request->wh_username_client,

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
        $contents_wh = WhmcsConfiguration::take(1)->first();
        if($contents_wh) {
            return $this->successResponse($contents_wh ,"Enregistrement effectué avec succès!",201);


        }
        else {
            return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
        }
    }
}
