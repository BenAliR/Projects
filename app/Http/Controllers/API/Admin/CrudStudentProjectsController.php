<?php

namespace App\Http\Controllers\API\Admin;

use App\Events\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\Project;
use App\Models\task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Snowfire\Beautymail\Beautymail;

class  CrudStudentProjectsController extends Controller
{
    use ApiResponser;

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
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {


        // Get the projects associated with the owned and member teams
        $projects = Project::with(['team.owner', 'team.users'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function show($id)
    {

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('id', $id)
            ->with('team.users','team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        return $this->successResponse($project,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Display an inserting of the resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function store(Request $request,$id)
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
        // Get the user
        $etudiant = User::find($id);

        if (!$etudiant) {

            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
            // Get the user
        }

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
        $project->user_id = $etudiant->id;
        $project->team_size = $request->team_size ;
        $project->slug =$slug;
        $project->team_id = null;

        // Save the project to the database
        $project->save();

        // Create a team for the project
        $team = new Team();
        $team->owner_id =$etudiant->id;
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
    public function updateStudentProject(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
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
     * @param $id
     * @return JsonResponse
     */
    public function getStudentProjects($id)
    {
        $etudiant = User::find($id);

        if (!$etudiant) {

            return $this->errorResponse(404, ['Étudiant introuvable'], 'quelque chose s\'est mal passé');
            // Get the authenticated user
        }
        // Get the team IDs owned by the user
        $ownedTeamIds = Team::where('owner_id', $etudiant->id)->pluck('id');

        // Get the team IDs in which the user is a member
        $memberTeamIds = $etudiant->teams()->pluck('id');

        // Get the projects associated with the owned and member teams
        $projects = Project::whereIn('team_id', $ownedTeamIds)
            ->orWhereIn('team_id', $memberTeamIds)
            ->with(['team.owner', 'team.users'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);


    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteProject($id)
    {
        try {

            $project = Project::find($id);

            if (!$project) {

                return $this->errorResponse(404, ['Projet introuvable'], 'quelque chose s\'est mal passé');
            }

            $project->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Projet introuvable'], 'quelque chose s\'est mal passé');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMultipleProjects(Request $request)
    {
        $projectIdsToDelete = $request->input('project_ids');
        $explode_id = array_map('intval', explode(',', $request->input('project_ids')));
        if (empty($projectIdsToDelete)) {
            return $this->errorResponse(400, ['ID projets non valides fournis.'], 'quelque chose s\'est mal passé');
        }

        try {

            foreach ($explode_id as $projectId) {
                $project = Project::findOrFail($projectId);

                $project->delete();
            }
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Un ou plusieurs projets introuvables.'], 'quelque chose s\'est mal passé');

        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function listTasks($id)
    {

        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('id', $id)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        $tasks = Task::where(['project_id'=> $project->id])
            ->orderBy('due_date')
            ->with('Assigned')
            ->get();
        // Add count of comments for each task
        $tasks->each(function ($task) {
            $task->comment_count = TaskComment::where('task_id', $task->id)->count();
        });
        $tasks->each(function ($task) {
            $task->subtasks_count = task::where('parent_id', $task->id)->count();
        });
        $message = "hello";

        // Send data to the Socket.io server
        $notification = [
            'message' => 'New task created: ' . $message,
            'link' => '/tasks/' . 1,
        ];

        event(new NotificationEvent($notification));

        return $this->successResponse($tasks ,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @param $taskId
     * @return JsonResponse
     */
    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);

        if (!$task) {
            return $this->errorResponse(404, ["Tâche introuvable"],"Tâche introuvable" );
        }

        // Delete the task
        $task->delete();
        return $this->successResponse([],"Enregistrement effectué avec succès!",201);
    }


    /**
     * Display a listing of the resource.
     *
     * @param $commentId
     * @return JsonResponse
     */
    public function deleteTaskComment($commentId)
    {
        $comment = TaskComment::findOrFail($commentId);

        if (!$comment) {
            return $this->errorResponse(404, ["Commentaire introuvable"],"Commentaire introuvable" );
        }
        // Delete the task
        $comment->delete();
        return $this->successResponse([],"Enregistrement effectué avec succès!",201);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function pendingInvitationsProject(Request $request, $id)
    {


        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('id', $id)
            ->with('team.owner')
            ->first();

        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
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
    public function getTeamMembersProject($projectId)
    {
        if ($projectId === null) {
            // Handle the case when $id is undefined or not provided
            // You can return a response or perform any desired logic
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Retrieve the authenticated user
        $user = Auth::user();

        // Retrieve the project by ID
        $project = Project::where('id', $projectId)
            ->with('team.users')
            ->first();
        if (!$project) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }

        // Retrieve the team members of the project's team
        $teamMembers = $project->team->users;
        // Return the team members in a JSON response
        return $this->successResponse($teamMembers,"Enregistrement effectué avec succès!",201);
    }




}
