<?php

namespace App\Http\Controllers\API;

use App\Events\NotificationEvent;
use App\Http\Controllers\Controller;

use App\Models\DemandeInscription;
use App\Models\Invite;
use App\Models\Project;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lexx\ChatMessenger\Models\Thread;
use Mpociot\Teamwork\Facades\Teamwork;
use App\Traits\ApiResponser;
use Snowfire\Beautymail\Beautymail;


class ProjetController extends Controller
{
    use ApiResponser;


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $user->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserTeams(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $user->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');


        // Combine the member and owner teams
        $teams = $ownedTeamIds->concat($memberTeamIds);

        return $this->successResponse($teams,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ExpandedList(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $user->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users','tasks','meetings'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getTeamMembersAndOwners()
    {
        // Add the user to the team with the specified role
//        $team->attachUser($user, ['role' => $role]);
        $user = Auth::user();

        // Retrieve the teams where the user is the owner
        $ownedTeams = $user->ownedTeams()->with(['owner','users'])->get();

        // Retrieve the teams where the user is a member
        $memberTeams = $user->teams()->with(['owner','users'])->get();

        // Extract the members from each team
        $ownedTeamMembers = $ownedTeams->pluck('users')->collapse();
        $memberTeamMembers = $memberTeams->pluck('users')->collapse();

        // Merge the members and owners into one array
        $teamMembers = $ownedTeamMembers->merge($memberTeamMembers)->unique('id');

        // Append the owners to the team members
        $owners = $memberTeams->pluck('owner')->unique('id');
        $teamMembers = $teamMembers->concat($owners);
        // Remove the item where id matches the authenticated user's id
        $teamMembers = $teamMembers->filter(function ($member) use ($user) {
            return $member->id !== $user->id;
        });
        // Transform the collection to keep only the desired attributes
        $teamMembers = $teamMembers->map(function ($member) {
            return [
                'id' => $member->id,
                'fullname' => $member->fullname,
                'photo' => $member->photo,
            ];
        })->values();
        return $this->successResponse($teamMembers,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show( Request $request,$slug)
    {
        // Retrieve the authenticated user
        $user = Auth::user();
        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $slug)
            ->with('team.users')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the user is a member of the team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }

        $notification = "[
            'title' => 'New Notification',
            'message' => 'You have a new notification!',
        ]";

        event(new NotificationEvent($notification));


        return $this->successResponse($project,"Enregistrement effectué avec succès!",201);
    }
    public function generateUniqueSlug($title)
    {
        // Generate a base slug from the title
        $slug = Str::slug($title);

        // Check if the slug already exists in the database
        $count = Project::where('slug', $slug)->count();

        // If the slug exists, append a unique identifier
        if ($count > 0) {
            $slug = $slug . '-' . uniqid();
        }

        return $slug;
    }
    /**
     * Display an inserting of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'nullable|string',
            'dev_technologie' => 'nullable|string',
            'domaine' => 'nullable|string',
            'team_name' => 'nullable|string|max:255',
            'team_size' => 'nullable|string|max:255',
        ]);

        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        // Get the currently authenticated user
        $user = Auth::user();
        $slug= $this->generateUniqueSlug($request->title);
        // Check if validation fails
        // Create a new project instance with the validated data
        $project = new Project();
        $project->title =  $request->title ;
        $project->duedate =  $request->duedate ;
        $project->description = $request->description ;
        $project->project_status = "en cours de traitement";
        $project->type = $request->type ;
            $project->dev_technologie = $request->dev_technologie ;

        $project->domaine = $request->domaine ;
        $project->user_id = $user->id;
        $project->team_size = $request->team_size ;
        $project->slug =$slug;
        $project->team_id = null;

        // Save the project to the database
        $project->save();

        // Create a team for the project
        $team = new Team();
        $team->owner_id =$user->id;
        $team->name =   $request->team_name ?? $project->title;
        $team->save();
//        // Add team members if provided
//        $user->attachTeam($team);

//        if (!empty($validator['team_members'])) {
//            $team->attachUsers($user->id);
//        }
//
//        // Invite team members if provided
//        if (!empty($validator['team_members'])) {
//            $team->inviteUsers($validator['team_members']);
//        }
        // Update the project with the team ID
        $project->team_id = $team->id;
        $project->save();
        // Return a JSON response indicating the successful creation of the project
        return $this->successResponse($project,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display an updating of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProject(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $authenticatedUserId = auth()->id();
        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the authenticated user is the owner of the project
        if ($project->user_id !== $authenticatedUserId) {
            return response()->json(['message' => 'You are not authorized to update this project.'], 403);
        }
        // Validate the request data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'nullable|string',
            'dev_technologie' => 'nullable|string',
            'team_name' => 'nullable|string|max:255',
            'team_size' => 'nullable|string|max:255',
            'duedate' => 'nullable|string',

        ]);
        // Update the project
        $project->title =  $request->title ;
        $project->duedate =  $request->duedate ;
        $project->description = $request->description ;
        $project->type = $request->type ;
        $project->dev_technologie = $request->dev_technologie ;
        $project->team_size = $request->team_size ;
        // Save the project to the database
        $project->save();
        $team = Team::findOrFail( $project->team_id);
        // Update the team name
        $team->name =  $request->team_name ?? $project->title;
        $team->save();


        // Return a JSON response indicating the successful updating of the project
        return $this->successResponse($project,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamMembers($projectId)
    {
        if ($projectId === null) {
            // Handle the case when $id is undefined or not provided
            // You can return a response or perform any desired logic
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID
        $project = Project::where('slug', $projectId)
            ->with('team.users')
            ->first();
        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the user is a member of the project's team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        // Retrieve the team members of the project's team
        $teamMembers = $project->team->users;
        // Return the team members in a JSON response
        return $this->successResponse($teamMembers,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display an inserting  of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inviteMember(Request $request, $slug)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID along with its team and owner

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $slug)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Check if the authenticated user is the owner of the team
        if ($user->id !== $project->team->owner->id) {
            return $this->errorResponse(403, ["Seul le propriétaire de l'équipe peut inviter des membres"],"Seul le propriétaire de l'équipe peut inviter des membres" );
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        // Retrieve the user by email
        $newuser = User::where('email', $request->input('email'))->first();

        // Check if the user is already a member of the project's team
        if ($newuser && $project->team->users->contains('id', $user->id)) {
            return $this->errorResponse(404, ["L'utilisateur est déjà membre de l'équipe"],"L'utilisateur est déjà membre de l'équipe" );
        }
        $email= $request->email;
        $projectname = $project->title;
        $teamname = $project->team->name;
        $name = $request->user()->fullname;
        if ($newuser && !$project->team->users->contains('id', $user->id)) {

            if( !Teamwork::hasPendingInvite( $request->email, $project->team) ) {


                Teamwork::inviteToTeam($email, $project->team, function ($invite) use ($teamname, $projectname, $name, $email) {
                    // Send email to user / let them know that they got invited
                    $body = [

                        'email' => $email,
                        'name' => $name,
                    ];
                    // send the email
                    $beautymail = app()->make(Beautymail::class);
                    $beautymail->send('emails.invitationoldmember',  ["data"=>$body], function($message) use( $body)
                    {
                        $message
                            ->from('email@email.com','Company')
                            ->to($body['email'] , $body['email'])
                            ->subject("Invitation pour participer à un projet");
                    });
                });
            }

   }elseif (!$newuser && !$project->team->users->contains('id', $user->id)){

            if( !Teamwork::hasPendingInvite( $request->email, $project->team) ) {
                Teamwork::inviteToTeam($email, $project->team, function ($invite) use ($teamname, $projectname, $name, $email) {
                    // Send email to user / let them know that they got invited

                    do {
                        //generate a random string using Laravel's str_random helper
                        $token = Str::random(30);
                    } //check if the token already exists and if it does, try again
                    while (Invite::where('token', $token)->first());
                    //create a new invite record
                    $invitation = Invite::create([
                        'email' => $email,
                        'token' => $token
                    ]);


                    $body = [
                        'token'=>$token,
                        'email' => $email,
                        'name' => $name,
                    ];
                    // send the email
                    $beautymail = app()->make(Beautymail::class);
                    $beautymail->send('emails.invitation',  ["data"=>$body], function($message) use( $body)
                    {
                        $message
                            ->from('email@email.com','Company')
                            ->to($body['email'] , $body['email'])
                            ->subject("Invitation pour participer à un projet");
                    });



                });
            }
        }
        // Return  a JSON response
        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
    public function pendingInvitations(Request $request, $slug)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID along with its team and owner

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $slug)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Check if the user is a member of the project's team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        $pendingInvitations = $project->team->invitations()->get();

        // Return  a JSON response
        return $this->successResponse($pendingInvitations,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getTeamInvites()
    {
        $authenticatedUserEmail = Auth::user()->email;

        // Retrieve the team invites for the authenticated user
        // Retrieve user invitations with the associated team and sender
        $userInvitations = TeamInvitation::where('email', $authenticatedUserEmail)
            ->with(['team','sender','team.project'])
            ->get();
        return $this->successResponse($userInvitations,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Display an updating of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function acceptInvitation(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();
        $rules = [

                'invitation_token' => 'required',

        ];
        $niceNames = array(

            'invitation_token' => 'invitation accept token',


        );
        $messages = array(

            'invitation_token.required' => 'Veuillez fournir token valide!',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        // create the user with the details from the invite
        $invite = Teamwork::getInviteFromAcceptToken( $request->invitation_token ); // Returns a TeamworkInvite model or null

        if( $invite ) // valid token found
        {
            Teamwork::acceptInvite( $invite );
            return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
        }else{
            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé' );

        }
        // delete the invite so it can't be used again

        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked


    }
    /**
     * Display an updating of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function denyInvitation(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [

            'invitation_token' => 'required',

        ];
        $niceNames = array(

            'invitation_token' => 'invitation deny token',


        );
        $messages = array(

            'invitation_token.required' => 'Veuillez fournir token valide!',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        // create the user with the details from the invite
        $invite = Teamwork::getInviteFromDenyToken( $request->invitation_token ); // Returns a TeamworkInvite model or null

        if( $invite ) // valid token found
        {
            Teamwork::denyInvite( $invite );
            return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
        }else{
            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé' );

        }
        // delete the invite so it can't be used again

        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function index1(Request $request)
    {
        //$Invitations1  = Team::where('owner_id','=',$request->user()->id)->with(['TeamOwner' ,'TeamProjects','users'])->get();
        $Invitations2  = Team::with(['TeamOwner:id,fullname,email,nom,prenom' ,'TeamProjects','users:id,fullname,email,nom,prenom'])->orderBy('created_at', 'DESC')->get();
        $list =[];
        foreach($Invitations2 as $team) {
            $idOwner = $team->owner_id;
            $team->isowner =  $request->user()->id ===$idOwner;
            if($team->hasUser($request->user()) ||$team->isowner){

               $list[] = $team;
           }
        }
        return $this->successResponse($Invitations2,"Enregistrement effectué avec succès!",201);


    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
        public function invitations(Request $request)
    {

        $Invitations  = DB::table('team_invites as i')
           ->where('i.email','=', $request->user()->email)
          ->join('teams as t', 'i.team_id', '=','t.id')
            ->leftJoin('projects as p', 't.id', '=',  'p.team_id')
         ->join('users as u', 't.owner_id', '=', 'u.id')
          ->select('i.*', 't.name','u.email as owner','u.fullname','p.title','p.project_avatar')
            ->get();
        return $this->successResponse($Invitations,"Enregistrement effectué avec succès!",201);

    }
    public function accept(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [

            'invitation_token' => 'required',

        ];
        $niceNames = array(

            'invitation_token' => 'invitation accept token',


        );
        $messages = array(

            'invitation_token.required' => 'Veuillez fournir token valide!',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        // create the user with the details from the invite
        $invite = Teamwork::getInviteFromAcceptToken( $request->invitation_token ); // Returns a TeamworkInvite model or null

        if( $invite ) // valid token found
        {
            Teamwork::acceptInvite( $invite );
            return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
        }else{
            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé' );

        }
        // delete the invite so it can't be used again

        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked


    }
    public function reject(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [

            'invitation_token' => 'required',

        ];
        $niceNames = array(

            'invitation_token' => 'invitation deny token',


        );
        $messages = array(

            'invitation_token.required' => 'Veuillez fournir token valide!',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }

        // create the user with the details from the invite
        $invite = Teamwork::getInviteFromDenyToken( $request->invitation_token ); // Returns a TeamworkInvite model or null

        if( $invite ) // valid token found
        {
            Teamwork::denyInvite( $invite );
            return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
        }else{
            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé' );

        }
        // delete the invite so it can't be used again

        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked


    }
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        $data = $request->all();
        $rules = [

            'titre_projet' => 'required',
            'description' => 'required',
            'type_projet' => 'required',
            'file' => 'required',
            'dev_technologie' => 'required',
            'domaine' => 'required',
            'static_image' => 'required',
        ];
        $niceNames = array(
             'file' => 'Avatar de projet',
            'titre_projet' => 'Titre du projet',
            'type_projet' => 'Type du projet',
            'nom_equipe' => 'Nom d\'équipe ',
            'domaine' => 'Nom domaine',
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
        if($request->static_image === 'null'){
            if($data['file']){
                $imagepath = Storage::disk('uploads')->put('/'.$request->user()->email.'/projets/'.$request->user()->id.'-'.substr( md5( $request->user()->id . '-' . time() ), 0, 7) , $data['file']);
                $img = '/uploads/' . $imagepath;
            }else{
                $img = '/uploads/ProjectAvatar/1.png';
            }


        }else{
            $img = '/uploads/ProjectAvatar/' . $request->static_image.'.png';
        }

        $Demande = DemandeInscription::where('user_id','=' ,$request->user()->id)->first();
        if(!$Demande) {


            return $this->errorResponse( 422,null, 'quelque chose s\'est mal passé');
        }
        $team = new Team();
        $team->owner_id = $request->user()->id;
        $team->name =  $request->nom_equipe;
        $team->save();

        $project =  Project::create(      [

            'demande_id' => $Demande->id,
            'title' => $request->titre_projet,
            'dev_technologie' => $request->dev_technologie,
            'description' => $request->description,
            'domaine' => $request->domaine,
         //   'slug' =>  $slug,
            'project_avatar' => $img,

            'type' => $request->type_projet,
            'team_id' => $team->id,
        ]);

             $newPost =$project->replicate();

        $name = $request->user()->fullname;
        $projectname =$request->nom_equipe;
        $teamname = $team->name;
        $equipe =        $request->equipe;
        if(count($equipe) > 0){
            foreach($equipe as $obj) {

                $email= $obj;
                if($email){
                    $user = User::where('email','=' ,$email)->first();
                    if($user){
                        Teamwork::inviteToTeam( $email, $team, function( $invite ) use ($teamname, $projectname, $name, $email) {
                            // Send email to user / let them know that they got invited
                            $data = [
                                'equipe'=>$teamname,
                                'user'=>$name,
                                'project'=> $projectname
                            ];

                            Mail::to($email)->send(new EquipeInvitation($invite,$data));
                        });
                    }else{
                        Teamwork::inviteToTeam( $email, $team, function( $invite ) use ($teamname, $projectname, $name, $email) {
                            // Send email to user / let them know that they got invited

                            do {
                                //generate a random string using Laravel's str_random helper
                                $token = Str::random(30);
                            } //check if the token already exists and if it does, try again
                            while (Invite::where('token', $token)->first());
                            //create a new invite record
                            $invitation = Invite::create([
                                'email' => $email,
                                'token' => $token
                            ]);
                            // send the email
                            Mail::to($email)->send(new InviteCreated($invitation));
                        });
                    }
                }



            }
        }
        return $this->successResponse(null,'Enregistrement effectué avec succès!', 201);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function InviteMember2(Request $request)
    {

        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'email' => 'required',

        ];
        $niceNames = array(
            'id' => 'id de projet',
            'email' => 'email membre',


        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom domaine',
            'email.required' => 'Veuillez fournir email de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        $project = Project::where('id','=',$request->id_projet)->with('ProjectTeam')->first();
        if($project) {
            if($project->ProjectTeam->where('owner_id', '=',$request->user()->id)->first()){
                $name = $request->user()->fullname;
                $projectname =$project->title;
                $teamname = $project->ProjectTeam->name;



                        $email= $request->email;
                        if($email){
                            $user = User::where('email','=' ,$email)->first();

                            if(!empty($user)){
                                if( !Teamwork::hasPendingInvite( $request->email, $project->ProjectTeam) ) {
                                    Teamwork::inviteToTeam($email, $project->ProjectTeam, function ($invite) use ($teamname, $projectname, $name, $email) {
                                        // Send email to user / let them know that they got invited
                                        $data = [
                                            'equipe' => $teamname,
                                            'user' => $name,
                                            'project' => $projectname
                                        ];

                                        Mail::to($email)->send(new EquipeInvitation($invite, $data));
                                    });
                                }
                            }
                            else{
                                if( !Teamwork::hasPendingInvite( $request->email, $project->ProjectTeam) ) {
                                    Teamwork::inviteToTeam($email, $project->ProjectTeam, function ($invite) use ($teamname, $projectname, $name, $email) {
                                        // Send email to user / let them know that they got invited

                                        do {
                                            //generate a random string using Laravel's str_random helper
                                            $token = Str::random(30);
                                        } //check if the token already exists and if it does, try again
                                        while (Invite::where('token', $token)->first());
                                        //create a new invite record
                                        $invitation = Invite::create([
                                            'email' => $email,
                                            'token' => $token
                                        ]);
                                        // send the email
                                        Mail::to($email)->send(new InviteCreated($invitation));
                                    });
                                }
                            }
                        }


                return $this->successResponse(true,"invitation envoyée avec succès",201);
            }else{
                return $this->successResponse(false,"vous n'êtes pas le propriétaire de ce projet",201);
            }
        }else{
            return $this->successResponse(null,
                "projet introuvable",201);
        }



    }
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function DeleteInvitation(Request $request)
    {

        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'email' => 'required',

        ];
        $niceNames = array(
            'id' => 'id de projet',
            'email' => 'email membre',


        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom domaine',
            'email.required' => 'Veuillez fournir email de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        $project = Project::where('id','=',$request->id_projet)->with('ProjectTeam')->first();
        if($project) {
            if($project->ProjectTeam->where('owner_id', '=',$request->user()->id)->first()){
             DB::table('team_invites')
                    ->where('email','=', $request->email)
                    ->where('team_id','=', $project->ProjectTeam->id)
                  ->delete();


                return $this->successResponse(true,"invitation envoyée avec succès",201);
            }else{
                return $this->successResponse(false,"vous n'êtes pas le propriétaire de ce projet",201);
            }
        }else{
            return $this->successResponse(null,
                "projet introuvable",201);
        }



    }
    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function DeleteMember(Request $request)
    {

        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'email' => 'required',

        ];
        $niceNames = array(
            'id' => 'id de projet',
            'email' => 'email membre',


        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom domaine',
            'email.required' => 'Veuillez fournir email de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        $project = Project::where('id','=',$request->id_projet)->with('ProjectTeam')->first();
        if($project) {
            if($project->ProjectTeam->where('owner_id', '=',$request->user()->id)->first()){
                $user = User::where('email','=',$request->email)->first();
                if($user){
                    $user->detachTeam($project->ProjectTeam);
                }else{
                    return $this->errorResponse(422, null,'membre introuvable' );

                }



                return $this->successResponse(null,"detach membre avec succès",201);
            }else{
                return $this->errorResponse(422, null,'vous n\'êtes pas le propriétaire de ce projet' );

            }
        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }


    }
    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $slug
     * @return \Illuminate\Http\Response
     */
    public function CheckOwner( Request $request,$slug)
    {

        $project = Project::where('slug','=',$slug)->with('ProjectTeam')->first();
        if($project) {
            if($project->ProjectTeam->where('owner_id', '=',$request->user()->id)->first()){
                return $this->successResponse(true,"vous êtes  le propriétaire de ce projet",201);
            }else{
                return $this->errorResponse(422, null,'vous n\'êtes pas le propriétaire de ce projet' );

            }
        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }



    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'titre_projet' => 'required',
            'description' => 'required',
            'type_projet' => 'required',
            'file' => 'required',
            'dev_technologie' => 'required',

            'static_image' => 'required',
        ];
        $niceNames = array(
            'id_projet' => 'id de projet',
            'file' => 'Avatar de projet',
            'titre_projet' => 'Titre du projet',
            'type_projet' => 'Type du projet',
            'nom_equipe' => 'Nom d\'équipe ',
            'static_image' => 'random avatar',
            'dev_technologie' => 'un framework ou une bibliothèque',

        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
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

        $project = Project::where('id','=',$request->id_projet)->with('ProjectTeam')->first();
        if($project) {
            if($project->ProjectTeam->where('owner_id', '=',$request->user()->id)->first()){


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



                return $this->successResponse(true,"mise à jour du projet réussie",201);
            }else{
                return $this->errorResponse(422, null,'vous n\'êtes pas le propriétaire de ce projet' );

            }
        }else{
            return $this->errorResponse(422, null,'projet introuvable' );

        }





    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Projet  $projet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Projet $projet)
    {
        //
    }
}
