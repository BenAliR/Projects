<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectThreads;
use App\Models\Thread;
use App\Services\ActivityService;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lexx\ChatMessenger\Models\Participant;

class ProjectThreadsController extends Controller
{
    use ApiResponser;

    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserThreads(Request $request)
    {
        $user = $request->user();


        // Retrieve threads where the user is the owner or a participant
        $threads = Thread::WhereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['messages', 'messages.user:id,fullname,photo,last_seen','messages.attachments', 'participants', 'participants.user:id,fullname,photo,last_seen'])
            ->latest('updated_at')
            ->get();
        // Add information about whether the thread is starred or not
        foreach ($threads as $thread) {
            $thread->is_starred = $thread->participants()->where('user_id', $user->id)->first()->pivot->starred ?? false;
//            $date = $thread->participants()->where('user_id', $user->id)->first()->last_read;
//            // Count the number of unread messages for each thread
//            $thread->unread = $thread->messages->where('created_at', '>',$date)->count();
            $thread->unread =  $thread->userUnreadMessagesCount($user->id);
            $thread->last_message =  $thread->getLatestMessageAttribute()->body ?? "" ;
            $thread->project =  $thread->threadconnection()->first()->project ?? null;
            $thread->owner = $thread->creator()->id === $request->user()->id ?? false;
        }
        return $this->successResponse($threads, 'Enregistrement effectué avec succès!');
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserStarredThreads(Request $request)
    {
        $user = $request->user();
        $threads = Thread::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['messages', 'participants'])
            ->withCount(['participants as is_starred' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest('updated_at')
            ->get();

        return $this->successResponse($threads, 'Threads retrieved successfully!');
    }


    public function generateUniqueSlug($title)
    {
        // Generate a base slug from the title
        $slug = Str::slug($title);

        // Check if the slug already exists in the database
        $count = Thread::where('subject', $slug)->count();

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
        $data = $request->all();
        $rules = [
            'slug' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'members' => 'required',
        ];

        $niceNames = [
            'slug' => 'id de projet',
            'subject' => 'subject',
            'message' => 'message',
            'members' => 'les members de conversation',
        ];

        $messages = [
            'slug.required' => 'Veuillez fournir l\'identifiant du projet.',
            'subject.required' => 'Veuillez fournir le sujet de la conversation.',
            'message.required' => 'Veuillez fournir le message.',
            'members.required' => 'Veuillez fournir les membres de la conversation.',
        ];

        $validator = Validator::make($data, $rules, $messages, $niceNames);

        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé.');
        }

        // Retrieve the authenticated user
        $user = Auth::user();
        // Retrieve the project by its slug along with its team and team members
        $project = Project::where('slug', $request->slug)
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

        $thread = Thread::create([
            'subject' => $request->subject,
            'avatar' => '/uploads/default/chat.png',
            'start_date' => Carbon::now(),
            'slug' => $this->generateUniqueSlug($request->subject)
        ]);

        ProjectThreads::create([
            'thread_id' => $thread->id,
            'project_id' => $project->id,
        ]);

        // Message
        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'body' => $request->message,
        ]);
        $thread->end_date =Carbon::now();
        $thread->save();
        // Sender
        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'last_read' => Carbon::now(),
        ]);

        // Recipients
        $thread->addParticipant(explode(',', $request->members));

        $files = $request->file('file');

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!empty($file) && in_array($file->getClientOriginalExtension(), ['jpg', 'png','jpeg', 'pdf', 'zip', 'docx', 'doc', 'pptx', 'ppt'])) {
                    $path = '/uploads/' . Storage::disk('uploads')->put('/' . $request->user()->email . '/projets/conversation/' . $thread->id . '/messages/' . $message->id, $file);
                    $filename = $request->user()->id . '-' . substr(md5($request->user()->id . '-' . Str::random(14)), 0, 15) . '.' . $file->getClientOriginalExtension();

                    Attachment::create([
                        'extension' => $file->getClientOriginalExtension(),
                        'message_id' => $message->id,
                        'path' => $path,
                        'filename' => $filename,
                        'type' => $file->getClientOriginalExtension(),
                    ]);
                }
            }
        }
        $this->activityService->logActivity('conversation', 'conversation crée', 'conversation', $thread->id, 'user',  $user->id);
        return $this->successResponse($thread, "Enregistrement effectué avec succès!", 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ReplyMessage(Request $request)
    {
        $data = $request->all();
        $rules = [

            'thread_id' => 'required',
            'message' => 'required',
        ];

        $niceNames = [

            'thread_id' => 'ID de conversation',
            'message' => 'message',
        ];

        $messages = [

            'thread_id.required' => 'Veuillez fournir l\'identifiant de la conversation.',
            'message.required' => 'Veuillez fournir le message de conversation.',
        ];

        $validator = Validator::make($data, $rules, $messages, $niceNames);

        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé.');
        }

        // Retrieve the authenticated user
        $user = Auth::user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', intval($request->thread_id))
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->with(['messages', 'messages.user:id,fullname,photo,last_seen','messages.attachments', 'participants', 'participants.user:id,fullname,photo,last_seen'])
        ->first();
        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }
        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'body' => $request->message,
        ]);
        $thread->end_date  = Carbon::now();
        $thread->save();

        $files = $request->file('file');

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!empty($file) && in_array($file->getClientOriginalExtension(), ['jpg', 'png','jpeg', 'pdf', 'zip', 'docx', 'doc', 'pptx', 'ppt'])) {
                    $path = '/uploads/' . Storage::disk('uploads')->put('/' . $request->user()->email . '/projets/conversation/' . $thread->id . '/messages/' . $message->id, $file);
                    $filename = $request->user()->id . '-' . substr(md5($request->user()->id . '-' . Str::random(14)), 0, 15) . '.' . $file->getClientOriginalExtension();

                    Attachment::create([
                        'extension' => $file->getClientOriginalExtension(),
                        'message_id' => $message->id,
                        'path' => $path,
                        'filename' => $filename,
                        'type' => $file->getClientOriginalExtension(),
                    ]);
                }
            }
        }
        $this->activityService->logActivity('conversation', 'réponse de conversation', 'conversation', $thread->id, 'user',  $user->id);

        // Retrieve the thread where the user is the owner or a participant
        $threadUpdated = Thread::where('id', intval($request->thread_id))
            ->with(['messages', 'messages.user:id,fullname,photo,last_seen','messages.attachments', 'participants', 'participants.user:id,fullname,photo,last_seen'])
            ->first();
        return $this->successResponse($threadUpdated    , "Enregistrement effectué avec succès!", 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function getThread(Request $request, $threadId)
    {
        $user = $request->user();

        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
            })
            ->with(['messages', 'messages.user:id,fullname,photo,last_seen','messages.attachments', 'participants', 'participants.user:id,fullname,photo,last_seen'])
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }
        $thread->is_starred = $thread->participants()->where('user_id', $user->id)->first()->pivot->starred ?? false;
        $thread->unread =  $thread->userUnreadMessagesCount($user->id) ;

        return $this->successResponse($thread, 'Enregistrement effectué avec succès!');
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function starThread(Request $request,$threadId)
    {
        $user = $request->user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->with(['participants'])
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }
        $thread->star($request->user()->id);

        $this->activityService->logActivity('conversation', 'conversation préférée', 'conversation', $thread->id, 'user',  $user->id);

        return $this->successResponse([], 'Enregistrement effectué avec succès!');
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function unstarThread(Request $request,$threadId)
    {
        $user = $request->user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->with(['participants'])
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }
        $thread->unstar($request->user()->id);
        $this->activityService->logActivity('conversation', 'conversation indésirable', 'conversation', $thread->id, 'user',  $user->id);

        return $this->successResponse([], 'Enregistrement effectué avec succès!');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function addThreadParticipant(Request $request,$threadId)
    {
        $data = $request->all();
        $rules = [
            'user_id' => 'required',
        ];
        $niceNames = [
            'user_id' => 'id de membre',
        ];
        $messages = [
            'user_id.required' => 'Veuillez fournir l\'identifiant du membre',
        ];

        $validator = Validator::make($data, $rules, $messages, $niceNames);
        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé');
        }

        $user = $request->user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }
        if ($thread->creator()->id === $request->user()->id) {
            $thread->addParticipant(explode(',', $request->user_id));
            $this->activityService->logActivity('conversation', 'ajouter un nouveau participant', 'conversation', $thread->id, 'user',  $user->id);

            return $this->successResponse([], 'Enregistrement effectué avec succès!', 201);
        } else {
            return $this->errorResponse(422, ['Vous n\'êtes pas le propriétaire'], 'Vous n\'êtes pas le propriétaire');
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function removeThreadParticipant(Request $request, $threadId)
    {
        $data = $request->all();
        $rules = [
            'user_id' => 'required',
        ];
        $niceNames = [
            'user_id' => 'id de membre',
        ];
        $messages = [
            'user_id' => 'Veuillez fournir l\'identifiant du membre',
        ];

        $validator = Validator::make($data, $rules, $messages, $niceNames);
        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé');
        }

        $user = $request->user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ['Conversation introuvable'], 'Conversation introuvable');
        }

        if ($thread->creator()->id === $request->user()->id) {
            $thread->removeParticipant(explode(',', $request->user_id));
            $this->activityService->logActivity('conversation', 'suppression un participant', 'conversation', $thread->id, 'user',  $user->id);

            return $this->successResponse([], 'Enregistrement effectué avec succès!', 200);
        } else {
            return $this->errorResponse(422, ['Vous n\'êtes pas le propriétaire'], 'Vous n\'êtes pas le propriétaire');
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function quitThread(Request $request, $threadId)
    {
        $user = $request->user();

        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->first();;

        if (!$thread) {
            return $this->errorResponse(404, ['Conversation introuvable'], 'Conversation introuvable');
        }

        if ($thread->creator()->id === $user->id) {
            $thread->delete();

            return $this->successResponse([], 'Conversation supprimée avec succès!', 200);
        } else {
            $thread->removeParticipant($user->id);
            $this->activityService->logActivity('conversation', 'quit la conversation', 'conversation', $thread->id, 'user',  $user->id);

            return $this->successResponse([], 'Suppression effectuée avec succès!', 200);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $threadId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, $threadId)
    {

        $user = $request->user();
        // Retrieve the thread where the user is a participant
        $thread = Thread::where('id', $threadId)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ['Conversation introuvable'], 'Conversation introuvable');
        }

        // Get the participant
        $participant = $thread->participants()->where('user_id', $user->id)->first();
        if (!$participant) {
            return $this->errorResponse(422, ['Participant introuvable'], 'Participant introuvable');
        }

        // Update the last read timestamp
        $thread->markAsRead($user->id);

        return $this->successResponse([], 'Marqué comme lu avec succès!', 200);
    }



    public function updateSubject(Request $request, $threadId)
    {
        $data = $request->all();
        $rules = [
            'subject' => 'required',
        ];
        $niceNames = [
            'subject' => 'sujet',
        ];
        $messages = [
            'subject' => 'Veuillez fournir le sujet',
        ];

        $validator = Validator::make($data, $rules, $messages, $niceNames);
        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'Quelque chose s\'est mal passé');
        }

        $user = $request->user();
        // Retrieve the thread where the user is the owner or a participant
        $thread = Thread::where('id', $threadId)
            ->where(function ($query) use ($user) {
                $query->WhereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->with(['participants'])
            ->first();

        if (!$thread) {
            return $this->errorResponse(404, ["Conversation introuvable"],"Conversation introuvable" );
        }

        if ($thread->creator()->id === $request->user()->id) {
            $thread->subject = $request->subject;
            $thread->save();
            $this->activityService->logActivity('conversation', 'update conversation', 'conversation', $thread->id, 'user',  $user->id);

            return $this->successResponse([], 'Mise à jour du sujet effectuée avec succès!', 200);
        } else {
            return $this->errorResponse(422, ['Vous n\'êtes pas le propriétaire'], 'Vous n\'êtes pas le propriétaire');
        }
    }




    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $messages = ProjectThreads::where('project_id', '=', 139)->select('thread_id as id')->get();
        $threads1 = Thread::forUser($request->user()->id)->where('starred','=', true)
            ->with('messages', 'messages.MessageAttachments', 'participants.user:id,fullname,nom,prenom,last_seen', 'messages.user:id,fullname,nom,prenom,last_seen')
         ->get();
        $threads2 = Thread::forUser($request->user()->id)->where('starred','=', false)
            ->with('messages', 'messages.MessageAttachments', 'participants.user:id,fullname,nom,prenom,last_seen', 'messages.user:id,fullname,nom,prenom,last_seen')
            ->get();
        $threads = $threads1->merge($threads2);
        $filter = [];
        foreach ($messages as $m) {
            $data = $threads->filter(function ($item) use ($m) {
                return $item->id == $m->id;
            })->first();
            if ($data) {
                array_push($filter, $data);
            }
        }

        return $this->successResponse($filter, "Enregistrement effectué avec succès!", 201);
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
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadMessagesCount(Request $request)
    {
        $messages = ProjectThreads::where('project_id', '=', 139)->select('thread_id as id')->get();
        $threads = $request->user()->unreadMessagesCount();
        $filter = [];
        foreach ($messages as $m) {
            $data = $threads->filter(function ($item) use ($m) {
                return $item->id == $m->id;
            })->first();
            if ($data) {
                array_push($filter, $data);
            }
        }
        return $this->successResponse(array_reverse($filter), "Enregistrement effectué avec succès!", 201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UsersArray(Request $request)
    {
        $messages = Thread::where('id', '=', 23)->with('participants.user:id,fullname,nom,prenom,last_seen')->first();
     $participants =   $messages->participants;
        $selected = [];
        $NewArray = [];
        foreach ($participants as $participant ) {


            //  $members->forget($key);
            array_push($NewArray, ['id'=>$participant->user->id,'fullname'=>$participant->user->fullname,'nom'=>$participant->user->nom,'prenom'=>$participant->user->prenom]);


        }
        $project = Project::where('id','=',139)->with('ProjectTeam','ProjectTeam.users:id,fullname,email,nom,prenom','ProjectTeam.TeamOwner:id,fullname,email,nom,prenom')->first();

        $members = $project->ProjectTeam->users->makeHidden('pivot');

        $members->push($project->ProjectTeam->TeamOwner);




            foreach ($members as $value) {



                array_push($NewArray, $value);



            }

//        $collection = new Collection();
//        foreach($members as $item){
//            $collection->push((object)[
//                'id'=> $item->id,
//            'fullname'=> $item->fullname,
//           'email' => $item->email,
//            'nom' => $item->nom,
//            'prenom'=> $item->prenom,
//
//            ]);
//
//        }

        return $this->successResponse($NewArray, "Enregistrement effectué avec succès!", 201);
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return bool | object
     */
    public function CheckMemberShip(Request $request)
    {

        $project = Project::where('id','=',$request->id_projet)->with('ProjectTeam','Shared')->first();

        if($project) {
            $idOwner = $project->ProjectTeam->owner_id;
            $project->isowner = $request->user()->id === $idOwner;
            if ($project->ProjectTeam->hasUser($request->user()) || $project->isowner) {
                return  $project;
            }
            else {
                return false;
            }
        }else {
            return false;
        }






    }




    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ReplyMessage2(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'message' => 'required',


        ];
        $niceNames = array(
            'id_projet' => 'id de projet',
            'id_thread' => 'id de conversation',
            'message' => 'message',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'message' => 'le Veuillez fournir 1ere message de conversation',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){
            $project = $this->CheckMemberShip($request);
            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message
            $message = Message::create([
                'thread_id' => $thread->id,
                'user_id' => $request->user()->id,
                'body' => $request->message,
            ]);

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()


            if ($files = $request->file) {
                if(count($files) > 0){
                    foreach ($files as $file) {
                        if(!empty($file)){
                            if ($file->getClientOriginalExtension() === 'jpg' or $file->getClientOriginalExtension() === 'png' or
                                $file->getClientOriginalExtension() === 'pdf' or $file->getClientOriginalExtension() === 'zip' or
                                $file->getClientOriginalExtension() === 'docx' or $file->getClientOriginalExtension() === 'doc' or
                                $file->getClientOriginalExtension() === 'pptx' or $file->getClientOriginalExtension() === 'ppt') {
                                $path = '/uploads/' . Storage::disk('uploads')->put('/' . $request->user()->email . '/projets/' . $project->id . '/conversation/' . $thread->id . '/messages/' . $message->id, $file);
                                $filename = $request->user()->id . '-' . substr(md5($request->user()->id . '-' . Str::random(14)), 0, 15) .'.'. $file->getClientOriginalExtension();

                                Attachment::create([
                                    'extension' => $file->getClientOriginalExtension(),
                                    'message_id' => $message->id,
                                    'path' => $path,
                                    'filename' => $filename,
                                    'type' => $file->getClientOriginalExtension(),

                                ]);
                            }
                        }



                    }
                }


                //store file into document folder


            }
//        }
            return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }

    /**
 * Store a newly created resource in storage.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
    public function RemoveThreadParticipant2(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message
            if($thread->creator()->id === $request->user()->id){
                $thread->removeParticipant($request->id_user);
                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
            }else{

                return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
            }

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function QuitThread2(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();
         //   return $this->successResponse($thread->creator()->id, "Enregistrement effectué avec succès!",201);
            // Message

                if($thread->creator()->id === $request->id_user){
                    $thread->delete() ;

                }else{
                    $thread->removeParticipant($request->id_user);
                }

                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
            }


            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }


        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function AddThreadParticipant2(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message
            if($thread->creator()->id === $request->user()->id){
                $thread->addParticipant(explode(',', $request->id_user));
                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
            }else{

                return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
            }

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
 * Store a newly created resource in storage.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
    public function MarkAsRead2(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message

            $thread->markAsRead($request->id_user);
            return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function Star(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message

            $thread->star($request->id_user);
            return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function UnStar(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'id_thread' => 'required',
            'id_user' => 'required',


        ];
        $niceNames = array(
            'id_projet.required' => 'id de projet',
            'id_thread.required' => 'id de conversation',
            'id_user.required' => 'id de membre',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',
            'id_thread' => 'Veuillez fournir id de conversation ',
            'id_user' => 'le Veuillez fournir id de membre',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){

            $thread = Thread::where('id', '=', $request->id_thread)->first();

            // Message

            $thread->unstar($request->id_user);
            return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

            // Sender

            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()



//        }

        }
        else{

            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProjectThreads  $projectThreads
     * @return \Illuminate\Http\Response
     */
    public function show(ProjectThreads $projectThreads)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProjectThreads  $projectThreads
     * @return \Illuminate\Http\Response
     */
    public function edit(ProjectThreads $projectThreads)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProjectThreads  $projectThreads
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProjectThreads $projectThreads)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProjectThreads  $projectThreads
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectThreads $projectThreads)
    {
        //
    }
}
