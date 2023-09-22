<?php

namespace App\Http\Controllers\API\Guest;

use App\Events\AssignmentNotification;
use App\Events\NotificationEvent;
use App\Events\NotificationEvent2;
use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteria;

use App\Models\Event;
use App\Models\Project;
use App\Models\task;
use App\Services\ActivityService;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;


class EventProjectOrTaskController extends Controller
{
    use ApiResponser;

    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }



    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $event = Event::all();
            return $this->successResponse($event, "Enregistrement effectué avec succès!", 201);



    }

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function FromToDayEvents()
    {
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
        $eventsToday = Event::whereDate('start_datetime', '>=', $today)->with('project','task')->get();


//        $this->activityService->logActivity('conversation', 'conversation crée', 'conversation', $thread->id, 'user',  $user->id);
//
//



        return $this->successResponse($eventsToday, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $event = Event::find($id);
        // Retrieve the authenticated user
        $user = Auth::user();
// Create the notification data.
        event(new NotificationEvent2($user, $event));
        if (!$event) {

            return $this->errorResponse(404, ['Event introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($event, "Enregistrement effectué avec succès!", 201);

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
    'description'=> 'required|string',
            'start_datetime'=> 'required',
            'end_datetime'=> 'required',
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

        $event = Event::create(
  [          'title' => $request->title,
            'description' => $request->description,
      'start_datetime'=> $request->start_datetime,
      'end_datetime'=> $request->end_datetime,
      'task_id'=> $request->task_id,
          ]
        );


        // Retrieve the authenticated user
        $user = Auth::user();

        activity()
            ->performedOn($event)
            ->causedBy($user)
            ->setEvent('Create')
            ->withProperties(['laravel' => 'awesome'])
            ->log('L\'utilisateur :causer.fullname a créé un nouvel événement #:subject.title,Laravel is :properties.laravel');
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $event = Event::findOrFail($id);
            if (!$event) {
                return $this->errorResponse(404, ["Event n'existe pas"], 'quelque chose s\'est mal passé');
            }



            $rules = [
                'title' => 'required|string',
                'description'=> 'required|string',
                'start_datetime'=> 'required',
                'end_datetime'=> 'required',

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
            $event->title = $request->input('title');

            $event->description = $request->input('description');

            $event->start_datetime = $request->input('start_datetime');

            $event->end_datetime = $request->input('end_datetime');

            $event->save();
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

            $event = Event::find($id);

            if (!$event) {

                return $this->errorResponse(404, ['Event introuvable'], 'quelque chose s\'est mal passé');
            }


            $event->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Event  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}
