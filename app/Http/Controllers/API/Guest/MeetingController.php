<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Team;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class MeetingController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $meetings = Meeting::with('project')->latest()->get();
            return $this->successResponse($meetings, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * Display a listing of the resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function projectMeetings($slug)
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
        // Check if the user is a member of the team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        $meetings = Meeting::where(['project_id'=> $project->id])
            ->orderBy('start_datetime', 'desc')

            ->get();

        return $this->successResponse($meetings ,"Enregistrement effectué avec succès!",201);

    }


    public function getMeetingsForUser()
    {
        // Retrieve the authenticated user
        $user = Auth::user();

        // Get the projects where the user is the owner or a team member

        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $user->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users'])
            ->get();

        // Retrieve all meetings in the user's projects
        $meetings = Meeting::whereHas('project', function ($query) use ($projects) {
            $query->whereIn('id', $projects->pluck('id'));
        })->with('project')->orderBy('start_datetime', 'desc')->get();
        return $this->successResponse($meetings, "Enregistrement effectué avec succès!", 201);
    }
    public function getMeetingsForUserToDay()
    {
        // Retrieve the authenticated user
        $user = Auth::user();
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
        // Get the projects where the user is the owner or a team member

        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $user->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $user->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users'])
            ->get();

        // Retrieve all meetings in the user's projects
        $meetings = Meeting::whereHas('project', function ($query) use ($today, $projects) {
            $query->whereIn('id', $projects->pluck('id'));
            $query->whereDate('start_datetime', '>=', $today);
        })->with('project')->orderBy('start_datetime', 'desc')->get();
        return $this->successResponse($meetings, "Enregistrement effectué avec succès!", 201);

    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {

            return $this->errorResponse(404, ['meeting introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($meeting, "Enregistrement effectué avec succès!", 201);

    }



    public function getMeetingsForProjectLimited($count,$projectId)
    {
        // Retrieve the authenticated user
        $user = Auth::user();
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
        // Retrieve the project by ID along with its team and owner

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $projectId)
            ->with('team.owner')
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
        // Retrieve the last "x" meetings related to tasks in the specified project
        $meetings = Meeting::where(['project_id'=> $project->id])->whereDate('start_datetime', '>=', $today)
            ->orderBy('start_datetime', 'asc')->latest()->take($count)->get();

        return $this->successResponse($meetings, "Enregistrement effectué avec succès!", 201);
    }



    /**
     * Display resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function store(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [
            'title' => 'required|string',
            'date' => 'required',
    'description'=> 'required|string',
            'project_id' => 'required|exists:projects,id',

        ];
        $niceNames = array(

            'title' => 'required',


        );
        $messages = array(

            'title.required' => 'Veuillez fournir titre!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        // Retrieve the project by ID
        $project = Project::where('slug', $request->project_id)
            ->first();
        // Retrieve the authenticated user
        $user = Auth::user();
        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Check if the user is a member of the team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }




        $meeting = Meeting::create(
  [          'title' => $request->title,
            'project_id' => $request->project_id,
            'description'=> $request->description,
            'date' => $request->date,]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $meeting = Meeting::findOrFail($id);
            if (!$meeting) {
                return $this->errorResponse(404, ["Meeting n'existe pas"], 'quelque chose s\'est mal passé');
            }

            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if the user is a member of the team or the owner of the teams
            $isMember = $meeting->project->team->users->contains('id', $user->id);
            $isOwner = $user->ownedTeams->contains('id', $meeting->project->team->id);

            if (!$isMember && !$isOwner) {
                return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
            }

            $rules = [
                'title' => 'required|string',
                'date' => 'required',
                'description'=> 'required|string',

            ];
            $niceNames = array(

                'title' => 'required',


            );
            $messages = array(

                'title.required' => 'Veuillez fournir titre!',


            );
            $validator = Validator::make($data, $rules,$messages, $niceNames);;

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $meeting->title = $request->input('title');

            $meeting->date = $request->input('date');
            $meeting->description = $request->input('description');
            $meeting->save();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500,  ["Une erreur s'est produite lors de la mise à jour"], "Une erreur s'est produite lors de la mise à jour");
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        try {

            $meeting = Meeting::find($id);

            if (!$meeting) {

                return $this->errorResponse(404, ['Meeting introuvable'], 'quelque chose s\'est mal passé');
            }


            $meeting->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Meeting  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}
