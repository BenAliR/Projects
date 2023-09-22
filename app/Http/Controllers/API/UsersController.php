<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class   UsersController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('Demande')->where('role','=' ,'etudiant')

            ->get();
        return $this->successResponse($users,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invited()
    {
        $users = User::where('role','=' ,'invite')->get();
        return $this->successResponse($users,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function ShowInvited($id)
    {
        $user = User::with('teams')->find($id);;
        if (!$user){

            return $this->errorResponse( 422,null, ' Utilisateur n\'existe pas' );
        }

        return $this->successResponse($user,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function ShowInvitedProject(Request $request,$id)
    {
        $user = User::find($id);;
        if (!$user){

            return $this->errorResponse( 422,null, ' Utilisateur n\'existe pas' );
        }
        $Invitations2  = Team::with(['TeamOwner:id,fullname,email,nom,prenom' ,'TeamProjects'])->orderBy('created_at', 'DESC')->get();
        $list =[];
        foreach($Invitations2 as $team) {
            $team->isowner =  $request->user()->id ===$id;
            if($team->hasUser($user) ||$team->isowner){

                $list[] = $team;
            }
        }
        return $this->successResponse($list,"Enregistrement effectué avec succès!",201);


    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ListProjects()
    {
        $projects = Project::with('project_demande')

            ->get();
        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function ShowProject($id)
    {

        $project = Project::where('id','=',$id)->with('ProjectTeam','ProjectTeam.TeamOwner:id,fullname,email,nom,prenom,photo','ProjectTeam.users:id,fullname,email,nom,prenom,photo','ProjectTeam.invites','Shared')->first();


        if($project) {


                return $this->successResponse($project,"Enregistrement effectué avec succès!",201);

        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

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
        $user = user::find($id);
        if(!$user) {
            return $this->errorResponse( 422,null, "utilisateur n'existe pas" );

        }
        DB::table('oauth_access_tokens')
            ->where('user_id', $id)
            ->update([
                'revoked' => true
            ]);
        $user->delete();

        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
}
