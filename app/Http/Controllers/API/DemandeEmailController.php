<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\DemandeEmail;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class DemandeEmailController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DemandeEmail  $demandeEmail
     * @return \Illuminate\Http\Response
     */
    public function show(DemandeEmail $demandeEmail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DemandeEmail  $demandeEmail
     * @return \Illuminate\Http\Response
     */
    public function edit(DemandeEmail $demandeEmail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DemandeEmail  $demandeEmail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DemandeEmail $demandeEmail)
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
        $EmailTemplate = DemandeEmail::find($id);
        if(!$EmailTemplate) {
            return $this->errorResponse( 422,null, "Demande Email n'existe pas" );


        }

        $EmailTemplate->delete();
        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);

    }
}
