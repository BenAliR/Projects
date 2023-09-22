<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppInfoConfiguration;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AppInfoConfigurationController extends Controller
{
    use ApiResponser;
    public function createConfiguration(Request $request) {


        $data = $request->all();
        $rules = [
            'app_name' => 'required',
            'logo' => 'required',

        ];
        $niceNames = array(
            'app_name' => 'nom d\'application',
            'logo' => 'logo d\'application',

        );
        $messages = array(
            'app_name' => 'Veuillez fournir nom d\'application',
            'logo' => 'Veuillez fournir logo d\'application',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $contents_info = AppInfoConfiguration::take(1)->first();



        if($contents_info){
            $imagepath = Storage::disk('uploads')->put('/'.'AppConfiguration/' .$request->app_name, $request->logo);



            $img = '/uploads/' . $imagepath;
            $contents_info->app_name= $request->app_name;
            $contents_info->app_url= $request->app_url;
            $contents_info->app_logo= $img;
            $contents_info->url_facebook= $request->url_facebook;
            $contents_info->url_linkedin= $request->url_linkedin;
            $contents_info->url_instagram= $request->url_instagram;
            $contents_info->url_website= $request->url_website;
            
            $contents_info->save();
            return $this->successResponse($contents_info ,"Enregistrement effectué avec succès!",201);


        }else{
            $imagepath = Storage::disk('uploads')->put('/'.'AppConfiguration/'.$request->app_name, $request->logo);



            $img = '/uploads/' . $imagepath;
            $configuration  =   AppInfoConfiguration::create([
                "app_name"        =>      $request->app_name,
                "app_url"        =>      $request->app_url,
                "app_logo"          =>     $img,
                "url_facebook"          =>      $request->url_facebook,
                "url_linkedin"    =>      $request->url_linkedin,
                "url_instagram"     =>      $request->url_instagram,
                "url_website"      =>      $request->url_website,

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
        $contents_arr = AppInfoConfiguration::take(1)->first();
        if($contents_arr) {
            return $this->successResponse($contents_arr ,"Enregistrement effectué avec succès!",201);


        }
        else {
            return $this->errorResponse(422, null,'Configuration  de l\'application manquante' );
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function AppConfiguration()
    {
        $contents_arr = AppInfoConfiguration::take(1)->first();
        if($contents_arr) {
            return $this->successResponse($contents_arr ,"Enregistrement effectué avec succès!",201);


        }
        else {
            return $this->successResponse(null ,"Enregistrement effectué avec succès!",201);

        }
    }
}
