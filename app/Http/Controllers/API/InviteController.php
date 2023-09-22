<?php

namespace App\Http\Controllers\API;
use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Mail\InviteCreated;
use App\Models\Invite;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mpociot\Teamwork\Teamwork;

class InviteController extends Controller
{
    use ApiResponser;




    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $Invitations  = DB::table('team_invites')->where('email','=', $request->user()->email)
            ->get();

        return $this->successResponse($Invitations,"Enregistrement effectué avec succès!",201);

    }
    public function invite()
    {

        $team = Teamwork::all();
        return response([
            'teams' => $team,
            'statut'=>"réussie!",


        ]);
        // show the user a form with an email field to invite a new user
    }
    public function process(Request $request)
    {
        // process the form submission and send the invite by email
        do {
            //generate a random string using Laravel's str_random helper
            $token = Str::random(20);
        } //check if the token already exists and if it does, try again
        while (Invite::where('token', $token)->first());
        //create a new invite record
        $invite = Invite::create([
            'email' => $request->get('email'),
            'token' => $token
        ]);
        // send the email
        Mail::to($request->get('email'))->send(new InviteCreated($invite));
        // redirect back where we came from
        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
    }
    public function accept(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [
            'nom_complet'    => 'required',


            'password' => 'required',
            'invitation_token' => 'required',

        ];
        $niceNames = array(

            'invitation_token' => 'invitation token',
            'nom_complet'    => 'nom complet',
            'password' => 'required',


        );
        $messages = array(

            'invitation_token.required' => 'Veuillez fournir token valide!',

            'nom_complet.required' => 'Veuillez fournir votre nom complet',
            'password.required' => 'Veuillez fournir votre mot de passe',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        if (!$invite = Invite::where('token', $request->invitation_token)->first()) {
            //if the invite doesn't exist do something more graceful than this
            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé');

        }
        // create the user with the details from the invite
        User::create(['fullname'=>$request->nom_complet,'photo' =>'/uploads/default/8.png','email' => $invite->email,'password'=>bcrypt($request->password),'role'=>'invite']);
        // delete the invite so it can't be used again
        $invite->delete();
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);

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
     * @param  \App\Models\Invite  $invite
     * @return \Illuminate\Http\Response
     */
    public function show(Invite $invite)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invite  $invite
     * @return \Illuminate\Http\Response
     */
    public function edit(Invite $invite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invite  $invite
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invite $invite)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invite  $invite
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invite $invite)
    {
        //
    }
}
