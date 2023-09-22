<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\task;
use App\Models\TaskComment;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class TaskController extends Controller
{
    use ApiResponser;


    /**
     * Display a listing of the resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function index($slug)
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
        $tasks = Task::where(['project_id'=> $project->id,'parent_id' => null])
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

        return $this->successResponse($tasks ,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function allTasks()
    {

        // Retrieve the authenticated user
        $user = Auth::user();

        $tasks = Task::where(['assign_id'=> $user->id])->orWhere(['user_id'=> $user->id])
            ->orderBy('due_date','asc')
            ->with('Assigned','creator','project')
            ->get();
        // Add count of comments for each task
        $tasks->each(function ($task) {
            $task->comment_count = TaskComment::where('task_id', $task->id)->count();
        });
        $tasks->each(function ($task) {
            $task->subtasks_count = task::where('parent_id', $task->id)->count();
        });

        return $this->successResponse($tasks ,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function allTasksToDay()
    {
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
        // Retrieve the authenticated user
        $user = Auth::user();

        $tasks = Task::where(['assign_id'=> $user->id,'due_date', '>=', $today])->orWhere(['user_id'=> $user->id,'due_date', '>=', $today])
            ->orderBy('due_date')
            ->with('Assigned','creator','project')
            ->get();
        // Add count of comments for each task
        $tasks->each(function ($task) {
            $task->comment_count = TaskComment::where('task_id', $task->id)->count();
        });
        $tasks->each(function ($task) {
            $task->subtasks_count = task::where('parent_id', $task->id)->count();
        });

        return $this->successResponse($tasks ,"Enregistrement effectué avec succès!",201);

    }

    public function generateUniqueSlug($title)
    {
        // Generate a base slug from the title
        $slug = Str::slug($title);

        // Check if the slug already exists in the database
        $count = task::where('slug', $slug)->count();

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'parent_id' => 'nullable|integer',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'priority' => 'required|integer',
            'task_type' => 'required|string',
            'due_date' => 'required|date',
            'assign_id' => 'required|exists:users,id',
            'project_slug' => 'required|exists:projects,slug',
            'tags' => 'nullable|array',
        ]);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        // Retrieve the project by ID
        $project = Project::where('slug', $request->project_slug)
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

        $task = new Task();
        $task->parent_id = $request->parent_id;
        $task->title = $request->title;
        $task->description = $request->description;
        $task->priority = $request->priority;
        $task->slug = $this->generateUniqueSlug($request->title);
        $task->status = "1";
        $task->due_date =  $request->due_date;
        $task->type =  $request->task_type;
        $task->user_id = $user->id;
        $task->assign_id = $request->assign_id;
        $task->project_id = $project->id;
        $task->tags = json_encode($request->tags);
        $task->save();
        return $this->successResponse($task,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param $taskId
     * @param $newStatus
     * @return JsonResponse
     */
    public function changeTaskStatus($taskId,Request $request)
    {
        $task = Task::findOrFail($taskId);


        $validator = Validator::make($request->all(),[

            'status' => 'required|string',

        ]);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        if (!$task) {
            return $this->errorResponse(404, ["Tâche introuvable"],"Tâche introuvable" );
        }
        // Check if the authenticated user is the owner of the task
        if ($task->user_id === auth()->id() || $task->assign_id === auth()->id()  ) {
            // Update the task status
            Task::where('parent_id', $task->id)->update(['status' => $task->status]);
            $task->status = $request->status;
            $task->save();

        }else{

            return $this->errorResponse(403, ["Vous n'êtes pas autorisé à modifier le statut de cette tâche."],"Vous n'êtes pas autorisé à modifier le statut de cette tâche." );

        }

        return $this->successResponse($task,"Enregistrement effectué avec succès!",201);
    }




    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTask($taskId,Request $request)
    {
        $validator = Validator::make($request->all(),[
            'parent_id' => 'nullable|integer',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'priority' => 'required|integer',
            'status' => 'required|string',
            'task_type' => 'required|string',
            'due_date' => 'required|date',
            'assign_id' => 'required|exists:users,id',
            'tags' => 'nullable|array',
        ]);
        $task = Task::findOrFail($taskId);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        if (!$task) {
            return $this->errorResponse(404, ["Tâche introuvable"],"Tâche introuvable" );
        }
        // Check if the authenticated user is the owner of the task
        if ($task->user_id === auth()->id() || $task->assign_id === auth()->id()  ) {
            // Update the task status
            Task::where('parent_id', $task->id)->update(['status' => $request->status]);
            $task->status = $request->status;
            $task->title = $request->title;
            $task->description = $request->description;
            $task->priority = $request->priority;
            $task->due_date =  $request->due_date;
            $task->type =  $request->task_type;
            $task->assign_id = $request->assign_id;
            $task->tags = json_encode($request->tags);
            $task->save();

        }else{

            return $this->errorResponse(403, ["Vous n'êtes pas autorisé à modifier le statut de cette tâche."],"Vous n'êtes pas autorisé à modifier le statut de cette tâche." );

        }

        return $this->successResponse($task,"Enregistrement effectué avec succès!",201);
    }


    /**
     * Display a listing of the resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function getTaskBySlug($slug)
    {
        $task = Task::where('slug', $slug)->with('parent','creator','Assigned','subtasks','comments','comments.author','project.owner')->first();

        if (!$task) {
            return $this->errorResponse(404, ["Tâche introuvable"],"Tâche introuvable" );
        }

        // Check if the authenticated user is a member of the project or the owner of the task
        $authenticatedUserId = auth()->id();
        $project = $task->project;
        // Retrieve the authenticated user
        $user = Auth::user();
        // Check if the user is a member of the team or the owner of the teams
        $isMember = $project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }
        return $this->successResponse($task,"Enregistrement effectué avec succès!",201);
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
        $authenticatedUserId = auth()->id();
        if (!$task) {
            return $this->errorResponse(404, ["Tâche introuvable"],"Tâche introuvable" );
        }
        // Check if the authenticated user is the owner of the task or the owner of the associated project
        $isOwner = $task->user_id === $authenticatedUserId;
        $isProjectOwner = $task->project->user_id === $authenticatedUserId;
        if (!$isOwner && !$isProjectOwner) {
            return $this->errorResponse(403, ["Vous n'êtes pas autorisé à supprimer cette tâche."],"Vous n'êtes pas autorisé à supprimer cette tâche." );
        }
        // Delete the task
        $task->delete();
        return $this->successResponse($task,"Enregistrement effectué avec succès!",201);
    }




    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addTaskComment(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'task_id' => 'required|exists:tasks,id',
            'comment' => 'nullable|string',

        ]);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        // Retrieve the project by ID
        $task = task::where('id', $request->task_id)
            ->first();
        if (!$task) {
            return $this->errorResponse(404, ["Projet introuvable"],"Projet introuvable" );
        }
        // Retrieve the authenticated user
        $user = Auth::user();

        // Check if the user is a member of the team or the owner of the teams
        $isMember = $task->project->team->users->contains('id', $user->id);
        $isOwner = $user->ownedTeams->contains('id', $task->project->team->id);

        if (!$isMember && !$isOwner) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire de ce projet"],"vous n'êtes pas le propriétaire de ce projet" );
        }

        $comment = new TaskComment();
        $comment->user_id = $user->id;
        $comment->task_id = $task->id;
        $comment->body = $request->comment;

        $comment->save();
        return $this->successResponse($comment,"Enregistrement effectué avec succès!",201);
    }


    /**
     * Display a listing of the resource.
     *
     * @param $commentId
     * @return JsonResponse
     */
    public function updateTaskComment(Request $request,$commentId)
    {
        $comment = TaskComment::findOrFail($commentId);
        $authenticatedUserId = auth()->id();
        $validator = Validator::make($request->all(),[

            'comment' => 'nullable|string',

        ]);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        if (!$comment) {
            return $this->errorResponse(404, ["Commentaire introuvable"],"Commentaire introuvable" );
        }
        // Check if the authenticated user is the owner of the task or the owner of the associated project
        $isOwner = $comment->user_id === $authenticatedUserId;
        if (!$isOwner) {
            return $this->errorResponse(403, ["Vous n'êtes pas autorisé à supprimer cette commentaire."],"Vous n'êtes pas autorisé à supprimer cette commentaire." );
        }
        // update comment
        $comment->body = $request->comment;
        $comment->save();
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
        $authenticatedUserId = auth()->id();
        if (!$comment) {
            return $this->errorResponse(404, ["Commentaire introuvable"],"Commentaire introuvable" );
        }
        // Check if the authenticated user is the owner of the task or the owner of the associated project
        $isOwner = $comment->user_id === $authenticatedUserId;
        $isProjectOwner = $comment->task->project->user_id === $authenticatedUserId;
        $isTaskOwner = $comment->task->user_id === $authenticatedUserId;
        if (!$isOwner && !$isProjectOwner && !$isTaskOwner) {
            return $this->errorResponse(403, ["Vous n'êtes pas autorisé à supprimer cette tâche."],"Vous n'êtes pas autorisé à supprimer cette tâche." );
        }
        // Delete the task
        $comment->delete();
        return $this->successResponse([],"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param $projectId
     * @return JsonResponse
     */
    public function tasksOverTime($projectId)
    {
        $tasksOverTime = Task::where('project_id', $projectId)
            ->selectRaw('DATE(due_date) as date, COUNT(*) as task_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        return $this->successResponse($tasksOverTime,"Enregistrement effectué avec succès!",201);

    }
    public function tasksSummary($projectId)
    {
        $summary = Task::where('project_id', $projectId)
            ->select(
                Task::raw('COUNT(CASE WHEN status = "0" THEN 1 END) as pending_count'),
                Task::raw('COUNT(CASE WHEN status = "1" THEN 1 END) as in_progress_count'),
                Task::raw('COUNT(CASE WHEN status = "2" THEN 1 END) as completed_count')
            )
            ->first();
        return $this->successResponse($summary,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index1(Request $request)
    {
        $data = $request->all();
        $rules = [

            'id_projet' => 'required',


        ];
        $niceNames = array(
            'id' => 'id de projet',



        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom domaine',




        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        $tasks = Task::with('subtasks','Comments','creator:id,fullname,nom,prenom')->where([

            'project_id' => $request->id_projet,
            'parent_id' => null,
        ])->orderBy('due_date')->get();

        return $this->successResponse($tasks,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function TasksToDo(Request $request)
    {



        $tasks = Task::whereBetween('due_date', [   (new Carbon)->subDays(7)->startOfDay()->toDateString(), Carbon::now()->endOfWeek()])->orderBy('due_date','desc')->with(['creator:id,fullname,nom,prenom','Project','parent', 'Comments' => function($q){
                $q->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);},'Comments.CommentAuthor:fullname,id,email'])->where('assign_id','=',$request->user()->id)->get();

        return $this->successResponse($tasks,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Marks a task or a subtask complete.
     *
     * @param  Request  $request
     * @return Response
     */
    public function complete(Request $request)
    {
        $data = $request->all();
        $rules = [

            'task_id' => 'required',



        ];
        $niceNames = array(
            'task_id' => 'id de tàche',
        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        $task = Task::findOrFail($request->task_id);

        $task->status = true;

        $task->save();
        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function UpdateAll(Request $request)
    {
        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'tasks'=> 'required',


        ];
        $niceNames = array(
            'id' => 'id de projet',
            'tasks'=> 'les tàches',





        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom projet',

            'tasks.required'=> 'Veuillez fournir les tàches ',




        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        $array =  json_decode($request->tasks);
        foreach ($array as $task) {
            Task::where('id', $task->id)->update(['status' => $task->status]);
        }

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);
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

//    /**
//     * Store a newly created resource in storage.
//     *
//     * @param  \Illuminate\Http\Request  $request
//     * @return \Illuminate\Http\Response
//     */
//    public function store(Request $request)
//    {
//        $data = $request->all();
//        $rules = [
//
//            'id_projet' => 'required',
//        'title'=> 'required',
//        'description'=> 'required',
//      'priority'=> 'required',
//
//     'due_date'=> 'required',
//      'task_status'=> 'required',
//            'assign_id'=> 'required',
//
//        ];
//        $niceNames = array(
//
//            'id' => 'id de projet',
//            'title'=> 'titre de  tàche',
//            'description'=> 'description de  tàche',
//            'priority'=> 'priorité de  tàche',
//
//            'due_date'=> 'date d\'échéance de  tàche',
//            'task_status'=> 'statut de  tàche',
//            'assign_id'=> 'attribuer à l\'utilisateur',
//
//
//
//
//        );
//        $messages = array(
//
//            'id_projet.required' => 'Veuillez fournir votre Nom projet',
//
//            'title.required'=> 'Veuillez fournir titre de  tàche',
//            'description.required'=> 'Veuillez fournir description de  tàche',
//            'priority.required'=> 'Veuillez fournir priorité de  tàche',
//
//            'due_date.required'=> 'Veuillez fournir date d\'échéance de  tàche',
//            'task_status.required'=> 'Veuillez fournir statut de  tàche',
//            'assign_id.required'=> 'Veuillez  attribuer à l\'utilisateur',
//
//
//
//        );
//        $validator = Validator::make($data, $rules,$messages, $niceNames);
//        if($validator->fails()) {
//
//            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
//        }
//        $task = Task::create([
//           // 'parent_id' => '',
//            'title' => $request->title,
//            'due_date' => date('Y-m-d', strtotime($request->due_date))  ,
//            'project_id' => $request->id_projet,
//            'user_id' =>  $request->user()->id,
//          'description' =>  $request->description,
//                      'assign_id' =>  $request->assign_id,
//                          'priority' =>  $request->priority,
//                        'status' =>  (int)$request->task_status,
//
//        ]);
//        $newPost =$task->replicate();
//          $array =  json_decode($request->subtask);
//        if(count($array) > 0){
//            foreach ($array as $p) {
//                $task2 = Task::create([
//                    'parent_id' => $task->id,
//                    'title' => $p->title,
//                    'due_date' => date('Y-m-d', strtotime($p->due_date))  ,
//                    'project_id' => $request->id_projet,
//                    'user_id' =>  $request->user()->id,
//
//
//                    'description' =>  $p->description,
//
//                    'assign_id' =>  $p->assign_id,
//                    'priority' =>  $p->priority,
//                    'status' =>  (int)$p->task_status,
//
//                ]);
//                $newPost =$task2->replicate();
//            }
//        }
//        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);
//
//    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeSub(Request $request)
    {
        $data = $request->all();
        $rules = [
            'task_id' => 'required',
            'id_projet' => 'required',
            'title'=> 'required',
            'description'=> 'required',
            'priority'=> 'required',

            'due_date'=> 'required',
            'task_status'=> 'required',
            'assign_id'=> 'required',

        ];
        $niceNames = array(

            'id' => 'id de projet',
            'title'=> 'titre de  tàche',
            'description'=> 'description de  tàche',
            'priority'=> 'priorité de  tàche',
            'task_id' => 'id de tàche',
            'due_date'=> 'date d\'échéance de  tàche',
            'task_status'=> 'statut de  tàche',
            'assign_id'=> 'attribuer à l\'utilisateur',




        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
            'id_projet.required' => 'Veuillez fournir votre Nom projet',

            'title.required'=> 'Veuillez fournir titre de  tàche',
            'description.required'=> 'Veuillez fournir description de  tàche',
            'priority.required'=> 'Veuillez fournir priorité de  tàche',

            'due_date.required'=> 'Veuillez fournir date d\'échéance de  tàche',
            'task_status.required'=> 'Veuillez fournir statut de  tàche',
            'assign_id.required'=> 'Veuillez  attribuer à l\'utilisateur',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $task = Task::findOrFail($request->task_id);
        $Subtask = Task::create([
            'parent_id' => $task->id,
            'title' => $request->title,
            'due_date' => date('Y-m-d', strtotime($request->due_date))  ,
            'project_id' => $request->id_projet,
            'user_id' =>  $request->user()->id,
            'description' =>  $request->description,
            'assign_id' =>  $request->assign_id,
            'priority' =>  $request->priority,
            'status' =>  (int)$request->task_status,

        ]);
        $newPost =$Subtask->replicate();

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'task_slug' => 'required',

        ];
        $niceNames = array(
            'id' => 'id de projet',
            'slug' => 'slug de tache',



        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom Projet',




        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        $tasks = Task::where([
                'project_id' => $request->id_projet,
                'slug' => $request->task_slug
            ])
            ->with('subtasks','Comments','Comments.CommentAuthor:id,fullname,nom,prenom','creator:id,fullname,nom,prenom','subtasks.creator:id,fullname,nom,prenom','AssignTo:id,fullname,nom,prenom','subtasks.AssignTo:id,fullname,nom,prenom')->first();

        return $this->successResponse($tasks,"Enregistrement effectué avec succès!",201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = $request->all();
        $rules = [
            'task_id' => 'required',
            'id_projet' => 'required',
            'title'=> 'required',
            'description'=> 'required',
            'priority'=> 'required',

            'due_date'=> 'required',
            'task_status'=> 'required',
            'assign_id'=> 'required',

        ];
        $niceNames = array(

            'id' => 'id de projet',
            'title'=> 'titre de  tàche',
            'description'=> 'description de  tàche',
            'priority'=> 'priorité de  tàche',
            'task_id' => 'id de tàche',
            'due_date'=> 'date d\'échéance de  tàche',
            'task_status'=> 'statut de  tàche',
            'assign_id'=> 'attribuer à l\'utilisateur',




        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
            'id_projet.required' => 'Veuillez fournir votre Nom projet',

            'title.required'=> 'Veuillez fournir titre de  tàche',
            'description.required'=> 'Veuillez fournir description de  tàche',
            'priority.required'=> 'Veuillez fournir priorité de  tàche',

            'due_date.required'=> 'Veuillez fournir date d\'échéance de  tàche',
            'task_status.required'=> 'Veuillez fournir statut de  tàche',
            'assign_id.required'=> 'Veuillez  attribuer à l\'utilisateur',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $task = Task::findOrFail($request->task_id);
        $task->update([
            // 'parent_id' => '',
            'title' => $request->title,
            'due_date' => date('Y-m-d', strtotime($request->due_date))  ,
            'project_id' => $request->id_projet,
            'user_id' =>  $request->user()->id,
            'description' =>  $request->description,
            'assign_id' =>  $request->assign_id,
            'priority' =>  $request->priority,
            'status' =>  (int)$request->task_status,

        ]);

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        $data = $request->all();
        $rules = [
            'task_id' => 'required',
            'id_projet' => 'required',
            'task_status'=> 'required',


        ];
        $niceNames = array(

            'id' => 'id de projet',

            'task_id' => 'id de tàche',

            'task_status'=> 'statut de  tàche',





        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
            'id_projet.required' => 'Veuillez fournir votre Nom projet',

            'task_status.required'=> 'Veuillez fournir statut de  tàche',




        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $task = Task::findOrFail($request->task_id);
        $task->update([
            // 'parent_id' => '',
            'user_id' =>  $request->user()->id,

            'status' =>  (int)$request->task_status,

        ]);

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function updateCommentaire(Request $request)
    {
        $data = $request->all();
        $rules = [
            'task_id' => 'required',
            'commentaire_id' => 'required',
            'id_projet' => 'required',
            'commentaire'=> 'required',


        ];
        $niceNames = array(

            'id' => 'id de projet',
            'commentaire'=> 'commentaire',
            'commentaire_id' => 'id de commentaire',
            'task_id' => 'id de tàche',





        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
            'id_projet.required' => 'Veuillez fournir votre Nom projet',
            'commentaire_id.required'=> 'Veuillez fournir id de  commentaire',
            'commentaire.required'=> 'Veuillez fournir votre commentaire',





        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $task = TaskComment::findOrFail($request->commentaire_id);
        $task->update([
            // 'parent_id' => '',
            'body' => $request->commentaire,


        ]);

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function storeCommentaire(Request $request)
    {
        $data = $request->all();
        $rules = [
            'task_id' => 'required',
            'id_projet' => 'required',
            'commentaire'=> 'required',


        ];
        $niceNames = array(

            'id' => 'id de projet',
            'commentaire'=> 'commentaire',
            'task_id' => 'id de tàche',





        );
        $messages = array(
            'task_id.required' => 'Veuillez fournir id tàche',
            'id_projet.required' => 'Veuillez fournir votre Nom projet',

            'commentaire.required'=> 'Veuillez fournir votre commentaire',





        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

       TaskComment::create([
            // 'parent_id' => '',

            'user_id' =>  $request->user()->id,
            'body' =>  $request->commentaire,
            'task_id' =>  $request->task_id,


        ]);

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\task  $task
     * @return \Illuminate\Http\Response
     */
    public function AssignToMe(Request $request)
    {
        $data = $request->all();
        $rules = [

            'id_projet' => 'required',
            'task_id' => 'required',

        ];
        $niceNames = array(
            'id' => 'id de projet',
            'task_id' => 'id de tàche',




        );
        $messages = array(

            'id_projet.required' => 'Veuillez fournir votre Nom projet',
            'task_id.required' => 'Veuillez fournir  id tàche',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        $task = Task::findOrFail($request->task_id);
        $task->update([
            // 'parent_id' => '',
            'user_id' =>  $request->user()->id,
            'assign_id' =>  $request->assign_id,


        ]);

        return $this->successResponse( null,"Enregistrement effectué avec succès!",201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        $task->delete();

        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroycommentaire($id)
    {
        $task = TaskComment::findOrFail($id);

        $task->delete();

        return $this->successResponse(null,"Enregistrement effectué avec succès!",201);
    }
}
