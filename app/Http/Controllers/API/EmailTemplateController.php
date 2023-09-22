<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $EmailTemplate = EmailTemplate::all();
        return $this->successResponse($EmailTemplate,"Enregistrement effectué avec succès!",201);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $EmailTemplate = EmailTemplate::create(      [

            'subject' => $request->subject,
            'sujet' => $request->sujet,
            'emailcontent' => $request->emailcontent,

        ]);


        return $this->successResponse($EmailTemplate,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $EmailTemplate = EmailTemplate::find($id);
        if (!$EmailTemplate){
            return $this->errorResponse( 422,null, "Email Template n'existe pas" );

        }
        return $this->successResponse($EmailTemplate,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
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
        $EmailTemplate = EmailTemplate::find($id);
        if(!$EmailTemplate) {
            return $this->errorResponse( 422,null, "Email Template n'existe pas" );

        }

        $EmailTemplate->delete();

        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
}
