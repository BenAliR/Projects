<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UniversiteDomaine;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UniversiteDomaineController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $list = UniversiteDomaine::all();
        return $this->successResponse($list ,"Enregistrement effectué avec succès!",201);

    }
    /**
     * check domaine
     *
     *
     * @param $domaine
     * @return \Illuminate\Http\Response
     */
    public function checkdomaine($domaine)
    {
        $domaines = Project::where('domaine', '=', $domaine)->first();

        if($domaines === null) {
            return response(['statut'=>'success','check'=> true,'message' =>'domaine  disponible']);
        }


        return response(['statut'=>'error','domain'=>$domaine,'check'=> false,'message' => 'domaine non disponible']);

    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',


        ];
        $niceNames = array(
            'domaine' => 'nom domaine',


        );
        $messages = array(
            'domaine' => 'Veuillez fournir nom domaine',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


            $configuration  =   UniversiteDomaine::create([
                "name"       =>      $request->domaine,

            ]);


            return $this->successResponse($configuration ,"Enregistrement effectué avec succès!",201);



    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UniversiteDomaine  $universiteDomaine
     * @return \Illuminate\Http\Response
     */
    public function show(UniversiteDomaine $universiteDomaine)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UniversiteDomaine  $universiteDomaine
     * @return \Illuminate\Http\Response
     */
    public function edit(UniversiteDomaine $universiteDomaine)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UniversiteDomaine  $universiteDomaine
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UniversiteDomaine $universiteDomaine)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $Domaine = UniversiteDomaine::find($id);
        if(!$Domaine) {
            return $this->errorResponse( 422,null, "domaine Email Not found" );

        }

        $Domaine->delete();
        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
}
