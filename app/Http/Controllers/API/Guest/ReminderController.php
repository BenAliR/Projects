<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteria;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Team;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class ReminderController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $reminder = Reminder::orderBy('reminder_datetime', 'desc')->get();;
            return $this->successResponse($reminder, "Enregistrement effectué avec succès!", 201);



    }
    public function getRemindersForProject($projectId)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

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
        // Retrieve reminders related to tasks in the specified project
        $reminders = Reminder::whereHas('task.project', function ($query) use ($user, $project) {
            $query->where('id', $project->id);
            $query->where('user_id', $user->id);
        })->orderBy('reminder_datetime', 'desc')->get();


        return $this->successResponse($reminders, "Enregistrement effectué avec succès!", 201);

    }


    public function getRemindersForProjectLimited($count,$projectId)
    {
        // Retrieve the authenticated user
        $user = Auth::user();

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
        // Retrieve the last "x" reminders related to tasks in the specified project
        $reminders = Reminder::whereHas('task.project', function ($query) use ($user, $project) {
               $query->where('id', $project->id);
            $query->where('user_id', $user->id);
        })->latest()->take($count)->get();

        return $this->successResponse($reminders, "Enregistrement effectué avec succès!", 201);
    }
    public function getRemindersForUser()
    {
        // Retrieve the authenticated user
        $user = Auth::user();
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
        // Get the projects where the user is the owner or a team member


        // Retrieve reminders along with task and project details

        $reminders = Reminder::where('user_id', $user->id)->whereDate('reminder_datetime', '>=', $today)->orderBy('reminder_datetime', 'desc')->get();;

        return $this->successResponse($reminders, "Enregistrement effectué avec succès!", 201);
    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function  show($id)
    {
        $reminder = Reminder::with('task','task.creator')->find($id);
        $user = Auth::user();
        if (!$reminder) {

            return $this->errorResponse(404, ['Reminder introuvable'], 'quelque chose s\'est mal passé');
        }
        if ($reminder->user_id != $user->id) {
            return $this->errorResponse(403, ["vous n'êtes pas le propriétaire "],"vous n'êtes pas le propriétaire" );
        }
        return $this->successResponse($reminder, "Enregistrement effectué avec succès!", 201);

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

        // Retrieve the authenticated user
        $user = Auth::user();
        $data = $request->all();


        $rules = [
            'title' => 'required|string',
    'description'=> 'required|string',
     'reminder_datetime'=> 'required',
    'task_id'=> 'required',

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

        $reminder = Reminder::create(
  [          'title' => $request->title,
            'description' => $request->description,
      'reminder_datetime'=> $request->reminder_datetime,
      'task_id'=> $request->task_id,
      'user_id' => $user->id
          ]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $reminder = Reminder::findOrFail($id);
            if (!$reminder) {
                return $this->errorResponse(404, ["reminder n'existe pas"], 'quelque chose s\'est mal passé');
            }
            $user = Auth::user();
            if ($reminder->user_id != $user->id) {
                return $this->errorResponse(403, ["vous n'êtes pas le propriétaire "],"vous n'êtes pas le propriétaire" );
            }

            $rules = [
                'title' => 'required|string',
                'description'=> 'required|string',
                'reminder_datetime'=> 'required',
                'checked'=> 'required',
                'task_id'=> 'required',

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
            $reminder->title = $request->input('title');

            $reminder->description = $request->input('description');

            $reminder->reminder_datetime = $request->input('reminder_datetime');
            $reminder->checked = $request->input('checked');

            $reminder->save();
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

            $reminder = Reminder::find($id);

            if (!$reminder) {

                return $this->errorResponse(404, ['reminder introuvable'], 'quelque chose s\'est mal passé');
            }
            $user = Auth::user();
            if ($reminder->user_id != $user->id) {
                return $this->errorResponse(403, ["vous n'êtes pas le propriétaire "],"vous n'êtes pas le propriétaire" );
            }

            $reminder->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['reminder  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}
