<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Models\Project;
use App\Models\ProjectThreads;
use App\Models\sharedData;
use App\Models\TicketAttachment;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Participant;
use Lexx\ChatMessenger\Models\Thread;
use Nullix\CryptoJsAes\CryptoJsAes;
use phpDocumentor\Reflection\Types\Collection;
use Solitweb\DirectAdmin\DirectAdmin;
use App\Traits\ApiResponser;
use stdClass;

class EtudiantController extends Controller
{
    use ApiResponser;
     public $username_client = 'grapYDri06nLT1iM8msLSOCijYBxyyA3';
    public $password_client = '1ygwi3aBAsyuDGYKxV1r0rBgfnXw4aBZ';
    public $accesskey = 'zenLSbUtPDMpw9b7a6zu67h5n8ksp82Cqf62utYHSpxfqeducation';
    public $url = 'https://c.education.zenhosting.tn/includes/api.php';

    /**
 * Display a listing of the resource.
 *
 * @return \Illuminate\Http\Response
 */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @param $whdata
     * @param Request $request
     * @return \Illuminate\Http\Client\Response
     */
    public function ClientConnectWH(Request $request,$whdata)
    {




        $response = Http::get($this->url, $whdata);
       return $response;




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
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return bool
     */
    public function ClientShareData(Request $request)
    {
if($this->CheckMemberShip($request)){
    $project = $this->CheckMemberShip($request);
    $whdata =   [

        'username' => $this->username_client,
        'password' =>  $this->password_client,
        'accesskey' =>  $this->accesskey,
        'action' => 'GetClientsProducts',
        'clientid' => $project->Shared->wh_id,
        'stats' => true,
        'responsetype' => 'json',


    ];
    $data = $this->ClientConnectWH($request,$whdata);

    if($data['result'] === 'success'){

        $filtered = Arr::where($data['products']['product'], function ($value) use ($project) {
            return $value['domain'] ===$project->domaine;
        });
        if(count($filtered) > 0){

            sharedData::updateOrCreate(
                [
                    'project_id' =>$project->id


                ],
                [
                    'username'=>$filtered[0]['username'],
                    'password'=> $filtered[0]['password'],
                    'status' => $filtered[0]['status'],
                    'url'=>$filtered[0]['serverhostname'],
                    'wh_id'=>$filtered[0]['clientid'],



                ]
            );
            return true;
        }else{
            return false;
        }




    }else{
        return false;

    }

}else{
    return false;
}




    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function GetClientProduct(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){
            $project = $this->CheckMemberShip($request);
            $whdata =   [

                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'action' => 'GetClientsProducts',
                'clientid' =>$project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $data1 = $this->ClientConnectWH($request,$whdata);
            $results = array();
            if($data1['result'] === 'success'){
                foreach($data1['products']['product'] as $obj) {
                    //   array_push($results, $obj['id'], $obj['name']);
                    array_push($results, array(
                        "id" =>$obj['id'],
                        "regdate" => $obj['regdate'],
                        "nom" => $obj['name'],
                        "domaine" => $obj['domain'],
                        "status" => $obj['status'],
                    ));

                }


                $res = collect($results)->where('domaine', '=', $project->domaine)->first();
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);



            }else{
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }


        }else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }



    }

    /**
     * Display a listing of the resource.
     *
     * @param $data
     * @param Request $request
     * @return bool|array
     */
    public function ClientConnectDirectAdmin(Request $request,$data)
    {





        if($data) {

            $api = new DirectAdmin;
            $api->connect('https://' . $data['serverhostname'], '2222');
            $api->set_login($data['username'], $data['password']);
            $api->set_method($data['method']);
            $api->query($data['query']);
            $res = $api->fetch_parsed_body();
            return $res;
        }

return false;




    }
    /**
     * Display a listing of the resource.
     *
     * @param $data
     * @param Request $request
     * @return bool|array
     */
    public function ClientConnectDirectAdminWithArray(Request $request,$data)
    {





        if($data) {

            $api = new DirectAdmin;
            $api->connect('https://' . $data['serverhostname'], '2222');
            $api->set_login($data['username'], $data['password']);
            $api->set_method($data['method']);
            $api->query($data['query'],$data['array']);
            $res = $api->fetch_parsed_body();
            return $res;
        }

        return false;




    }
    /**
     * Display a listing of the resource.
     *
     * @param $data
     * @param Request $request
     * @return bool|array
     */
    public function ClientConnectDirectAdminWithOutApi(Request $request,$data)
    {





        if($data) {

            $api = new DirectAdmin;
            $api->connect('https://' . $data['serverhostname'], '2222');
            $api->set_login($data['username'], $data['password']);
            $api->set_method($data['method']);
            $api->query($data['query'],$data['array']);
            $res = $api->fetch_body();
            return $res;
        }

        return false;




    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function GetTickets(Request $request)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){
            $project = $this->CheckMemberShip($request);
            $whdata =   [

                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'clientid' =>$project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',
                'action' => 'GetTickets',




            ];
            $data1 = $this->ClientConnectWH($request,$whdata);
            $results = array();

            if($data1['result'] === 'success'){
                if( $data1['totalresults'] > 0){
                    foreach ($data1['tickets']['ticket'] as $obj) {
                        if( $obj['service'] ==='S'.$project->serviceid){
                            array_push($results, array(
                                "id" => $obj['ticketid'],
                                "deptid" => $obj['deptid'],
                                "deptname" => $obj['deptname'],
                                "date" => $obj['date'],
                                "subject" => $obj['subject'],
                                "status" => $obj['status'],
                                "priority" => $obj['priority'],
                                "lastreply" => $obj['lastreply'],
                                "attachments" => $obj['attachments'],
                                "service" => $obj['service'],

                            ));

                        }

                    }
                }
//                $filtered = collect($results)->where('service', '=', 'S'.$project->serviceid)->all();
//                                $filtered = Arr::where($results, function ($value) use ($project) {
//                    return $value['service'] === 'S'.$project->serviceid;
//                });
                $res = new stdClass();

                $res->total =  count($results);

                $res->tickets = $results;

                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);



            }
            else{
                return $this->errorResponse(422,null,'quelque chose s\'est mal passé' );

            }


        }

        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

//        $originalValue = ["We do encrypt an array", "123", ['nested']]; // this could be any value
//        $password = "123456";
//        $encrypted = CryptoJsAes::encrypt($originalValue, $password);
//// something like: {"ct":"g9uYq0DJypTfiyQAspfUCkf+\/tpoW4DrZrpw0Tngrv10r+\/yeJMeseBwDtJ5gTnx","iv":"c8fdc314b9d9acad7bea9a865671ea51","s":"7e61a4cd341279af"}
//
//// decrypt
//        $encrypted1 = '{"ct":"g9uYq0DJypTfiyQAspfUCkf+\/tpoW4DrZrpw0Tngrv10r+\/yeJMeseBwDtJ5gTnx","iv":"c8fdc314b9d9acad7bea9a865671ea51","s":"7e61a4cd341279af"}';
//        $password1 = "123456";
//        $decrypted1= CryptoJsAes::decrypt($encrypted1, $password1);
//
//        echo "Encrypted: " . $encrypted . "\n";
//        echo "Decrypted: " . print_r($decrypted1) . "\n";

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function UpdateTicket(Request $request,$id)
    {

        $data = $request->all();
        $rules = [
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){
            $project = $this->CheckMemberShip($request);
            $whdata =   [

                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'clientid' =>$project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',
                'action' => 'GetTickets',




            ];
            $data1 = $this->ClientConnectWH($request,$whdata);
            $results = array();

            if($data1['result'] === 'success'){
                if( $data1['totalresults'] > 0)
                {

                    foreach($data1['tickets']['ticket'] as $obj) {
                        array_push($results, $obj['ticketid']);

                    }
                    if(in_array($id, $results)){
                        $whdata2 =   [

                            'username' => $this->username_client,
                            'password' =>  $this->password_client,
                            'accesskey' =>  $this->accesskey,
                            'action' => 'UpdateTicket',
                            'status' => 'Closed',
                            'ticketid' => $id,
                            'stats' => true,
                            'responsetype' => 'json',




                        ];
                        $data2 = $this->ClientConnectWH($request,$whdata2);


                    }else{
                        return $this->errorResponse(422, null,'billet introuvable' );

                    }
                        if($data2['result'] === 'success'){
                            //   return  $response1;
                            return $this->successResponse(null, "Enregistrement effectué avec succès!",201);

                        }else{

                            return $this->errorResponse(422, null, "quelque chose s'est mal passé");

                        }
                    }




                    else{
                        return $this->errorResponse(422, null,'billet introuvable' );
                    }

                }


        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }


        return $this->successResponse(null, "Enregistrement effectué avec succès!",201);



    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function GetTicket(Request $request,$id)
    {
        $data = $request->all();











        $rules = [
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'id_projet' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)){
            $project = $this->CheckMemberShip($request);





            // Recipients
//        if ($request->has('recipients')) {
            // add code logic here to check if a thread has max participants set
            // utilize either $thread->getMaxParticipants()  or $thread->hasMaxParticipants()

         //   $thread->addParticipant(3);
//        }

            $whdata =   [

                'username' => $this->username_client,
                'password' =>  $this->password_client,
                'accesskey' =>  $this->accesskey,
                'clientid' =>$project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',
                'action' => 'GetTickets',




            ];


            $data1 = $this->ClientConnectWH($request,$whdata);
            $results = array();
        if($data1['result'] === 'success'){
            if( $data1['totalresults'] > 0) {
                foreach ($data1['tickets']['ticket'] as $obj) {

                        array_push($results, $obj['ticketid']);


                }
                if (in_array($id, $results)) {
                    $whdata2 = [

                        'username' => $this->username_client,
                        'password' => $this->password_client,
                        'accesskey' => $this->accesskey,
                        'action' => 'GetTicket',
                        'repliessort' => 'ASC',
                        'ticketid' => $id,
                        'stats' => true,
                        'responsetype' => 'json',


                    ];
                    $data2 = $this->ClientConnectWH($request, $whdata2);
                }
            }else{

                return $this->errorResponse(422, null,'billet introuvable' );
         }

        if($data1['totalresults'] > 0){
         //   return  $response1;

            $data3 = array();
            foreach($data2['replies']['reply'] as $obj) {
                //   array_push($results, $obj['id'], $obj['name']);
                array_push($data3, array(
                    "id" =>$obj['replyid'],
                    "date" => $obj['date'],
                    "type" =>'reply',
                    "message" =>$obj['message'],
                    "attachments" => $obj['attachments'],
                    "requestor_type" =>$obj['requestor_type'],


                ));

            }
            $attachment = TicketAttachment::where('ticket_id','=',$data2['ticketid'])->get();
            $results = array(
                "id" => $data2['ticketid'],
                "deptid" => $data2['deptid'],
                "deptname" => $data2['deptname'],
                "requestor_type" => $data2['requestor_type'],
                "date" => $data2['date'],
                'type' =>'ticket',
                "subject" =>$data2['subject'],
                "status" => $data2['status'],
                "priority" => $data2['priority'],
                "attachments" => $data2['attachments'],
                "lastreply" => $data2['lastreply'],
                "replies" => $data3,
                "attachments2" => $attachment

            );

            return $this->successResponse($results, "Enregistrement effectué avec succès!",201);


        }else{
            return $this->errorResponse(422, null,'billet introuvable' );
        }
            }
            else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }






    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function GetTicketAttachment(Request $request,$id)
    {
        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'type' => 'required'

        ];
        $niceNames = array(
            'id_projet' => 'id de projet',
            'type' => 'type ticket / reply',

        );
        $messages = array(
            'id_projet.required' => 'Veuillez fournir id de projet',

            'type.required' => 'Veuillez fournir type ticket ou reply',
        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);
        if($validator->fails()) {

            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [

                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',
                'action' => 'GetTickets',


            ];


            $response = $this->ClientConnectWH($request, $whdata);
            $results = array();


            if ($response['result'] === 'success') {
                foreach ($response['tickets']['ticket'] as $obj) {
                    array_push($results, $obj['ticketid']);

                }

                if (in_array($id, $results)) {

                    $whdata1 = [

                        'username' => $this->username_client,
                        'password' => $this->password_client,
                        'accesskey' => $this->accesskey,
                        'clientid' => $project->Shared->wh_id,
                        'action' => 'GetTicket',
                        'repliessort' => 'ASC',
                        'ticketid' => $id,
                        'stats' => true,
                        'responsetype' => 'json',



                    ];
                    $response3 = $this->ClientConnectWH($request, $whdata1);





                    if ($response3['result'] === 'success') {
                        //   return  $response1;
                        $data = array();
                        if ($request->type === 'reply') {
                            foreach ($response3['replies']['reply'] as $obj1) {
                                array_push($data, $obj1['replyid']);

                            }

                            if (in_array($request->idreply, $data)) {

                                $whdata2 = [

                                    'username' => $this->username_client,
                                    'password' => $this->password_client,
                                    'accesskey' => $this->accesskey,
                                    'clientid' => $project->Shared->wh_id,
                                    'action' => 'GetTicketAttachment',
                                    'relatedid' => (int)$request->idreply,
                                    'type' => 'reply',
                                    'index' => $request->index,
                                    'stats' => true,
                                    'responsetype' => 'json',



                                ];


                                $response1 = $this->ClientConnectWH($request, $whdata2);

                                //   return $response1;

                                if ($response1['result'] === 'success') {
                                    $results1 = array(

                                        "filename" => $response1['filename'],
                                        "data" => $response1['data'],


                                    );
                                    return $this->successResponse($results1, "Enregistrement effectué avec succès!",201);

                                }
                                else {
                                    return $this->successResponse([], "Enregistrement effectué avec succès!",201);
                                }

                            } else {
                                $response = ["message" => "quelque chose s'est mal passé"];
                                return response($response, 422);
                            }
                        }

                        if ($request->type === 'ticket') {
                            $whdata3 = [

                                'username' => $this->username_client,
                                'password' => $this->password_client,
                                'accesskey' => $this->accesskey,
                                'clientid' => $project->Shared->wh_id,
                                'action' => 'GetTicketAttachment',
                                'relatedid' => $id,
                                'type' => 'ticket',
                                'index' => '0',
                                'stats' => true,
                                'responsetype' => 'json',



                            ];


                            $response1 = $this->ClientConnectWH($request, $whdata3);



                            if ($response1['result'] === 'success') {
                                $results2 = array(

                                    "filename" => $response1['filename'],
                                    "data" => $response1['data'],


                                );
                                return $this->successResponse($results2, "Enregistrement effectué avec succès!",201);

                            } else {
                                return $this->successResponse([], "Enregistrement effectué avec succès!",201);

                            }

                        }
                    }
                } else {
                    return $this->errorResponse(422, null,'billet introuvable' );
                }

            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }



    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function GetDepartments(Request $request)
    {

        $whdata =   [

            'username' => $this->username_client,
            'password' =>  $this->password_client,
            'accesskey' =>  $this->accesskey,
            'action' => 'GetSupportDepartments',
            'ignore_dept_assignments'=> true,
            'stats' => true,
            'responsetype' => 'json',




        ];
        $response = $this->ClientConnectWH($request,$whdata);

       $results = array();
if($response['result'] === 'success'){
    foreach($response['departments']['department'] as $obj) {
        //   array_push($results, $obj['id'], $obj['name']);
        array_push($results, array(
            "id" =>$obj['id'],
            "nom" => $obj['name']

        ));

    }
    return $this->successResponse($results, "Enregistrement effectué avec succès!",201);


}else{
    return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
}



    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function OpenTicket(Request $request)
    {

        $data = $request->all();
        $rules = [
            'id_projet' => 'required',
            'domaine' => 'required',
            'deptid'    => 'required',
            'ticketsubject' =>'required',
            'ticketmessage' => 'required',
            'priority' => 'required',


        ];
        $niceNames = array(
            'id_projet_' => 'id de projet',
            'deptid' => 'id du département',
            'ticketsubject' => 'objet de billet',
            'ticketmessage' => 'message de billet',
            'priority' => 'priorité de billet',


        );
        $messages = array(
            'id_projet.required' => 'Veuillez fournir id de projet',
            'deptid.required' => 'Veuillez fournir id du département',
            'ticketsubject.required' => 'Veuillez fournir votre objet de billet',
            'ticketmessage.required' => 'Veuillez nous indiquer votre message de billet!',

            'priority.required' =>'Veuillez nous indiquer la priorité de billet',
            'domaine.required' =>'Veuillez nous indiquer nom domaine',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            if (!empty($request->file('file'))) {
                $stack1 = array();

                foreach ($data['file'] as $file) {

                    array_push($stack1, $file->getClientOriginalName());
                }


                $adprofile = implode(',', $stack1);


                $array = [

                    'username' => $this->username_client,
                    'password' => $this->password_client,
                    'accesskey' => $this->accesskey,
                    'action' => 'OpenTicket',
                    'serviceid' =>  $project->serviceid,
                    'deptid' => $request->deptid,
                    'subject' => $request->ticketsubject,
                    'message' => $request->ticketmessage,
                    'clientid' => $project->Shared->wh_id,
                    'priority' => $request->priority,
                    'markdown' => true,
                    //   'attachments' => base64_encode(json_encode([['name' => $data['file'][0]->getClientOriginalName(), 'data' => $img1]])),
                    'responsetype' => 'json',


                ];


            } else {
                $array = [


                    'username' => $this->username_client,
                    'password' => $this->password_client,
                    'accesskey' => $this->accesskey,
                    'action' => 'OpenTicket',
                    'serviceid' =>  $project->serviceid,
                    'deptid' => $request->deptid,
                    'subject' => $request->ticketsubject,
                    'message' => $request->ticketmessage,
                    'clientid' => $project->Shared->wh_id,
                    'priority' => $request->priority,
                    'markdown' => true,
                    'responsetype' => 'json',


                ];
            }


            $response = $this->ClientConnectWH($request,$array);


            if ($response['result'] === 'success') {

                if (!empty($request->file('file'))) {
                    $stack = array();
                    if ($files = $request->file) {
                        foreach ($files as $file) {
                            if ($file->getClientOriginalExtension() === 'jpg' or $file->getClientOriginalExtension() === 'png' or
                                $file->getClientOriginalExtension() === 'pdf' or $file->getClientOriginalExtension() === 'zip' or
                                $file->getClientOriginalExtension() === 'docx' or $file->getClientOriginalExtension() === 'doc' or
                                $file->getClientOriginalExtension() === 'pptx' or $file->getClientOriginalExtension() === 'ppt') {
                                $path = '/uploads/' .Storage::disk('uploads')->put('/tickets/files/' . $response['id'], $file);
                                $filename = $request->user()->id . '-' .$file->getClientOriginalName();
                                array_push($stack, 'http://' . request()->getHost() . '/uploads/' . $path);
                                TicketAttachment::create([
                                    'reply_id' => $response['id'],
                                    'ticket_id' => $response['id'],
                                    'path' => $path,
                                    'filename' => $filename,
                                    'type' => $file->getClientOriginalExtension(),

                                ]);
                            }


                        }
                    }
                    $adprofile1 = implode(',', $stack);

                    $whdata = [

                        'username' => $this->username_client,
                        'password' => $this->password_client,
                        'accesskey' => $this->accesskey,
                        'action' => 'AddTicketNote',
                        'ticketid' => $response['id'],
                        'message' => $adprofile1,
                        'stats' => true,
                        'responsetype' => 'json',


                    ];


                    $response3 =  $this->ClientConnectWH($request,$whdata);



                }
                return $this->successResponse(['id'=>$response['id']], "Enregistrement effectué avec succès!",201);


            }
            else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function AddTicketReply(Request $request)
    {

        $data = $request->all();
        $rules = [
            'ticketid'    => 'required',
            'replymessage' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'ticketid' => 'id  de billet',

            'replymessage' => 'message de reponse billet',

            'id_projet' => 'id de projet',

        );
        $messages = array(
            'ticketid.required' => 'Veuillez fournir id de billet',

            'replymessage.required' => 'Veuillez nous indiquer votre reponse de billet!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);

            if (!empty($request->file('file'))) {
                $stack1 = array();

                foreach ($data['file'] as $file) {

                    array_push($stack1, $file->getClientOriginalName());
                }


                $adprofile = implode(',', $stack1);


                $array = [

                    'username' => $this->username_client,
                    'password' => $this->password_client,
                    'accesskey' => $this->accesskey,
                    'clientid' => $project->Shared->wh_id,
                    'action' => 'AddTicketReply',

                    'ticketid' => $request->ticketid,
                    'message' => $request->replymessage,

                    'markdown' => true,


                    'responsetype' => 'json',


                ];
            } else {
                $array = [

                    'username' => $this->username_client,
                    'password' => $this->password_client,
                    'accesskey' => $this->accesskey,
                    'action' => 'AddTicketReply',
                    'clientid' => $project->Shared->wh_id,
                    'ticketid' => $request->ticketid,
                    'message' => $request->replymessage,


                    'markdown' => true,
                    'responsetype' => 'json',


                ];
            }

            $whdata =  [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetTickets',
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response =$this->ClientConnectWH($request,$whdata);



            $results = array();

            if ($response['result'] === 'success') {
                if( $response['totalresults'] > 0) {
                    foreach ($response['tickets']['ticket'] as $obj) {
                        array_push($results, $obj['ticketid']);

                    }

                    if (in_array($request->ticketid, $results)) {


                        $response3 = $this->ClientConnectWH($request, $array);


//return $response3;
                        if ($response3['result'] === 'success') {

                            if (!empty($request->file('file'))) {
                                $stack = array();
                                if ($files = $request->file) {
                                    foreach ($files as $file) {
                                        if ($file->getClientOriginalExtension() === 'jpg' or $file->getClientOriginalExtension() === 'png' or
                                            $file->getClientOriginalExtension() === 'pdf' or $file->getClientOriginalExtension() === 'zip' or
                                            $file->getClientOriginalExtension() === 'docx' or $file->getClientOriginalExtension() === 'doc' or
                                            $file->getClientOriginalExtension() === 'pptx' or $file->getClientOriginalExtension() === 'ppt') {
                                            $path = '/uploads/' . Storage::disk('uploads')->put('/tickets/files/' . $request->ticketid . '/replies', $file);
                                            $filename = $request->user()->id . '-' .$file->getClientOriginalName() ;
                                            array_push($stack, 'http://' . request()->getHost() . '/uploads/' . $path);
                                            TicketAttachment::create([
                                                'reply_id' => $request->ticketid,
                                                'ticket_id' => $request->ticketid,
                                                'path' => $path,
                                                'filename' => $filename,
                                                'type' => $file->getClientOriginalExtension(),

                                            ]);
                                        }


                                    }

                                    //store file into document folder


                                }

                                $adprofile2 = implode(',', $stack);

                                $whdata2 = [


                                    'username' => $this->username_client,
                                    'password' => $this->password_client,
                                    'accesskey' => $this->accesskey,
                                    'action' => 'AddTicketNote',
                                    'ticketid' => $request->ticketid,
                                    'message' => $adprofile2,
                                    'stats' => true,
                                    'responsetype' => 'json',


                                ];
                                $response4 = $this->ClientConnectWH($request, $whdata2);


                                if ($response4['result'] === 'success') {
                                    return $this->successResponse(null, "Enregistrement effectué avec succès!", 201);
                                }
                            }


                        }
                    }
                    else {
                        return $this->errorResponse(422, null,'billet introuvable' );
                    }
                }
                else{
                    return $this->errorResponse(422, null,'billet introuvable' );
                }

            } else {
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }




    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function UpdateClient(Request $request)
    {
        $username_client = 'grapYDri06nLT1iM8msLSOCijYBxyyA3';
        $password_client = '1ygwi3aBAsyuDGYKxV1r0rBgfnXw4aBZ';
        $accesskey = 'zenLSbUtPDMpw9b7a6zu67h5n8ksp82Cqf62utYHSpxfqeducation';

        $response = Http::get('https://c.education.zenhosting.tn/includes/api.php', [


            'username' => $username_client,
            'password' => $password_client,
            'accesskey' => $accesskey,
            'action' => 'GetTicketAttachment',

            'type' => 'reply',
            'relatedid' => '3',
            'index' => '0',
            'stats' => true,
            'responsetype' => 'json',


        ]);
        return $response;

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientLoginDirectAdmin(Request $request)
    {
        $username_client = 'grapYDri06nLT1iM8msLSOCijYBxyyA3';
        $password_client = '1ygwi3aBAsyuDGYKxV1r0rBgfnXw4aBZ';
        $accesskey = 'zenLSbUtPDMpw9b7a6zu67h5n8ksp82Cqf62utYHSpxfqeducation';

        $whdata = [


            'username' => $this->username_client,
            'password' => $this->password_client,
            'accesskey' => $this->accesskey,
            'action' => 'GetClientPassword',
            'userid' =>  $request->user()->wh_id,
            'responsetype' => 'json',


        ];
        $response = $this->ClientConnectWH($request, $whdata);
        return $response;
//        $api = new DirectAdmin;
//        $domain = 'domaine.zenhosting.app';
//        http://51.38.16.218:2222/
//        $api->connect('http://' . '151.80.250.161', '2222');
//        //    $api->connect('https://' .$domain);
//                                                                    //                      $api->set_login('domaine','vtzsjDDHWp');
//      $api->set_login('admin','wRUpAFiDK/3w6t1z');
//     $api->set_method('GET');



    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomain(Request $request)
    {

        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(


            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


        ];
        $response = $this->ClientConnectWH($request, $whdata);

        if($response['result'] === 'success'){
            $DirectAdminData =[


                'serverhostname' => $response['products']['product'][0]['serverhostname'],
                'username' => $response['products']['product'][0]['username'],
                'password' => $response['products']['product'][0]['password'],
                'method' => 'GET',
                'query' => '/CMD_API_ADDITIONAL_DOMAINS',



            ];

       $res =     $this->ClientConnectDirectAdmin($request,$DirectAdminData);

            if(!empty($res))
            {
                $results = array();
                $domaine = str_replace('.', '_', $response['products']['product'][0]['domain']);
                $out = str_replace('=', ':', $res[$domaine]);
                $result1 =[];
                $array = explode('&', $out);
                foreach ($array as $data ) {
                    $result1[explode(':', $data, 2)[0]] = explode(':', $data, 2)[1];
                }
              $results = [
                  "id" =>$response['products']['product'][0]['id'],
                  "details" => $result1,
                  "domain" => $response['products']['product'][0]['domain'],
                  "status" => $response['products']['product'][0]['status'],
                  "username"  => $response['products']['product'][0]['username'],
                  "password" => $response['products']['product'][0]['password'],

              ];
                return $this->successResponse($results, "Enregistrement effectué avec succès!",201);
            }
            else{

                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }else{
            return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
        }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminModifyAccount(Request $request)
    {

        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(


            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
                if(intval($request->bandwidth) >= 1000 ){
                    $bandwidth = "unlimited";
                    $bandwidthtype = "ubandwidth";

                }else{
                    $bandwidth = intval($request->bandwidth);
                    $bandwidthtype = "bandwidth";
                }
                if(intval($request->quota) >= 1000 ){
                    $quota = "unlimited";
                    $quotatype = "uquota";
                }else{
                    $quota = intval($request->quota);
                    $quotatype = "quota";
                }
            }
            $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' =>'/CMD_API_DOMAIN',
                    'array' => array('action'=>'modify',$bandwidthtype=>$bandwidth,$quotatype =>$quota,
                        'force_redirect'=>$request->force_redirect ,'ssl'=>$request->ssl,'cgi'=>$request->cgi,'php'=>$request->php,'domain'=>$project->domaine),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);

            if($res['error'] === "0"){
                return $this->successResponse(null, "Enregistrement effectué avec succès!",201);
            }else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminSubDomain(Request $request)
    {
        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(


            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }


        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){

                $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' =>'/CMD_API_SUBDOMAIN_BANDWIDTH',
                    'array' =>  array('domain'=>$request->domaine),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);


            }else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

//        if(count($res) > 0){
// $results = $res;
//        }else{
//            $results = [];
//        }
//            return  response([
//                'status'         => 200,
//                'liste' => $results
//            ]);
//        }else{
//            $response = ["message" => "quelque chose s'est mal passé"];
//            return response($response, 422);
        }else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }

    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminAddSubDomain(Request $request)
    {

        $data = $request->all();
        $rules = [
            'subdomain'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'subdomain' => "Nom sous domaine",

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(
            'subdomain.required' => "Veuillez fournir Nom sous domaine",

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){



                $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' =>'/CMD_API_SUBDOMAINS',
                    'array' =>  array('action'=>'create','domain' => $request->domaine,'subdomain'=>$request->subdomain),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);





            if($res['error'] === "0"){
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
        }

    }
    else{
        return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDeleteSubDomain(Request $request)
    {
        $data = $request->all();
        $rules = [
            'subdomain'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'subdomain' => "Nom sous domaine",

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(
            'subdomain.required' => "Veuillez fournir Nom sous domaine",

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){



                $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' =>'/CMD_API_SUBDOMAINS',
                    'array' =>  array('action'=>'delete','domain' => $request->domaine  ,'select0'=>($request->subdomain),'contents'=>'yes'),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);

            if($res['error'] === "0")
            {
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }
            else
                {
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }


            }
            else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainPointers(Request $request)
    {
        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(


            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',

        );
        $messages = array(


            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }

        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){



                $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' =>'/CMD_API_DOMAIN_POINTER',
                    'array' =>   array('domain' => $request->domaine),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);

            if(count($res)> 0){
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }else{
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }


            }
            else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminAddPointer(Request $request)
    {

        $data = $request->all();
        $rules = [
            'pointer'    => 'required',

            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'pointer' => "Nom pointer",
            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',


        );
        $messages = array(
            'pointer.required' => "Veuillez fournir pointer",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){

                if($request->alias === 'yes'){
                $array = array('action'=>'add','domain' => $request->domaine,'from'=>$request->pointer,'alias'=>'yes');
                }else{
                    $array = array('action'=>'add','domain' => $request->domaine,'from'=>$request->pointer);
                }

                $DirectAdminData =[


                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' =>'/CMD_API_DOMAIN_POINTER',
                    'array' =>   $array,



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);

            if($res['error'] === "0"){
                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }else{
                return $this->errorResponse(422, null,$res['details'] );
            }

            }
            else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainDeletePointer(Request $request)
    {
        $data = $request->all();
        $rules = [
            'pointer'    => 'required',

            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'pointer' => "Nom pointer",
            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',


        );
        $messages = array(
            'pointer.required' => "Veuillez fournir pointer",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata =[


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
                $DirectAdminData =[
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' =>'/CMD_API_DOMAIN_POINTER',
                    'array' =>   array('action'=>'delete','domain' => $request->domaine,'select0'=>($request->pointer)),



                ];

                $res =   $this->ClientConnectDirectAdminWithArray($request,$DirectAdminData);

                return $this->successResponse($res, "Enregistrement effectué avec succès!",201);
            }
            else{
                return $this->errorResponse(422, null,'quelque chose s\'est mal passé' );
            }

        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|bool|\Illuminate\Http\JsonResponse
     */
    public function ClientDirectAdminZoneDNS(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',

            'id_projet' => 'id de projet',


        );
        $messages = array(

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);


        if($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(),'quelque chose s\'est mal passé' );
        }
        if($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $results = array();

                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_DNS_CONTROL',
                    'array' => array('json' => 'yes', 'domain' => $request->domaine, 'info' => 'yes', 'urlencoded' => 'yes', 'full_mx_records' => 'yes', 'redirect' => 'yes', 'ttl' => 'yes'),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);

                if ($res) {
                    $results = $res;
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }
                return $results;
            }
            else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');

            }
        }   else{

            return $this->errorResponse(422, null, "vous n'êtes pas le propriétaire de ce projet");
        }

    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminSSL(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',
        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_SSL',
                    'array' => array('json' => 'yes', 'domain' => $request->domaine, 'dnsproviders' => 'yes', 'redirect' => 'yes'),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);


                if ($res) {
                    return $res;
                    //return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }
            } else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminEditSSL(Request $request)
    {
        $data = $request->all();
        $rules = [
            'ssl' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',
            'hash_select'=> 'required',
        ];
        $niceNames = array(
            'ssl' => 'liste ssl',
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
            'hash_select' => 'type hash',
        );
        $messages = array(
            'ssl.required' => 'Veuillez fournir liste ssl',
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
            'hash_select.required' => 'Veuillez fournir type hash',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);
            $items = explode(',', $request->ssl);
            if ($response['result'] === 'success') {
            $results = array();
                $data = [];
                for ( $i = 0; $i < count($items); $i++ ) {
                    $data += [ "le_select$i" => $items[$i] ];
                }

                $data2 = array_merge($data, array('type'=>'create','request'=>'letsencrypt','name'=>$request->domaine,'keysize'=>'secp384r1','encryption'=>$request->hash_select,
                    'wildcard'=>'no','background'=>'auto','action'=>'save',
                    'domain'=>$request->domaine));
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_SSL',
                    'array' => $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);


                return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);

            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }
        else{
                return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");

        }


    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminEditZoneDNS(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',
            'enregistrement_name' => 'required',
            'enregistrement_value' => 'required',
            'enregistrement_type' => 'required',
        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
            'enregistrement_name' => 'Nom Enregistrement',
            'enregistrement_value' => 'Valeur Enregistrement',
            'enregistrement_type' => 'Type Enregistrement',
        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
            'enregistrement_name.required' => 'Veuillez fournir Nom Enregistrement',
            'enregistrement_value.required' => 'Veuillez fournir Valeur Enregistrement',
            'enregistrement_type.required' => 'Veuillez fournir Type Enregistrement',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $results = array();
                $data2 = array();
                $name = $request->enregistrement_type;
                if ($name == 'A') {
                    $data2 = array('domain' => $request->domaine, 'action' => 'edit', 'type' => $request->enregistrement_type
                    , 'name' => $request->enregistrement_name, 'value' => $request->enregistrement_value, 'ttl' => $request->enregistrement_ttl,
                        'arecs0' => $request->action_edit);
                }
                if ($name == 'NS') {
                    $data2 = array('domain' => $request->domaine, 'action' => 'edit',
                        'nsrecs0' => $request->action_edit);
                }
                if ($name == 'MX') {
                    $data2 = array('domain' => $request->domaine, 'action' => 'edit', 'type' => $request->enregistrement_type
                    , 'name' => $request->enregistrement_name, 'value' => $request->enregistrement_value, 'mx_value' => $request->enregistrement_value_mx, 'affect_pointers' => 'yes', 'ttl' => $request->enregistrement_ttl,
                        'mxrecs0' => 'name:' . $request->enregistrement_name . '&value:' . $request->enregistrement_value . ' ' . $request->enregistrement_value_mx);
                }
                if ($name == 'CNAME') {
                    $data2 = array('domain' => $request->domaine, 'action' => 'edit', 'type' => $request->enregistrement_type
                    , 'name' => $request->enregistrement_name, 'value' => $request->enregistrement_value, 'ttl' => $request->enregistrement_ttl,
                        'cnamerecs0' => $request->action_edit);
                }
                if ($name == 'TXT') {
                    $data2 = array('domain' => $request->domaine, 'action' => 'edit', 'type' => $request->enregistrement_type
                    , 'name' => $request->enregistrement_name, 'value' => $request->enregistrement_value, 'ttl' => $request->enregistrement_ttl,
                        'txtrecs0' => $request->action_edit);
                }
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_DNS_CONTROL',
                    'array' => $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

                if ($res['error'] === "0") {
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }

            } else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminAddZoneDNS(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',
            'enregistrement_name' => 'required',
            'enregistrement_value' => 'required',
            'enregistrement_type' => 'required',
        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
            'enregistrement_name' => 'Nom Enregistrement',
            'enregistrement_value' => 'Valeur Enregistrement',
            'enregistrement_type' => 'Type Enregistrement',
        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
            'enregistrement_name.required' => 'Veuillez fournir Nom Enregistrement',
            'enregistrement_value.required' => 'Veuillez fournir Valeur Enregistrement',
            'enregistrement_type.required' => 'Veuillez fournir Type Enregistrement',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $data2 = array();
                $name = $request->enregistrement_type;
                if($name == 'NS'){
                    $data2 = array('action'=>'add','domain'=>$request->domaine,'type'=>$request->enregistrement_type
                    ,'name'=>$request->enregistrement_value,'value'=>$request->enregistrement_name,'ttl'=>$request->enregistrement_ttl);
                }
                if($name == 'CNAME'){
                    $data2 = array('action'=>'add','domain'=>$request->domaine,'type'=>$request->enregistrement_type
                    ,'name'=>$request->enregistrement_name,'value'=>$request->enregistrement_value,'ttl'=>$request->enregistrement_ttl);
                }
                if($name == 'TXT'){
                    $data2 = array('action'=>'add','domain'=>$request->domaine,'type'=>$request->enregistrement_type
                    ,'name'=>$request->enregistrement_name,'value'=>$request->enregistrement_value,'ttl'=>$request->enregistrement_ttl);
                }
                if($name == 'A'){
                    $data2 = array('action'=>'add','domain'=>$request->domaine,'type'=>$request->enregistrement_type
                    ,'name'=>$request->enregistrement_name,'value'=>$request->enregistrement_value,'ttl'=>$request->enregistrement_ttl);
                }
                if($name == 'MX'){
                    $data2 = array('action'=>'add','domain'=>$request->domaine,'type'=>$request->enregistrement_type
                    ,'name'=>$request->enregistrement_name,'affect_pointers'=>'yes','value'=>$request->enregistrement_value,'mx_value'=>$request->enregistrement_value_mx,'ttl'=>$request->enregistrement_ttl);
                }


                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_DNS_CONTROL',
                    'array' => $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);


                if ($res['error'] === "0") {
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }


            } else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDeleteZoneDNS(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',
            'enregistrement_name' => 'required',
            'enregistrement_value' => 'required',
            'enregistrement_type' => 'required',
            'action_delete' => 'required',

        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
            'enregistrement_name' => 'Nom Enregistrement',
            'enregistrement_value' => 'Valeur Enregistrement',
            'enregistrement_type' => 'Type Enregistrement',
            'action_delete' => 'dns name and value',
        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
            'enregistrement_name.required' => 'Veuillez fournir Nom Enregistrement',
            'enregistrement_value.required' => 'Veuillez fournir Valeur Enregistrement',
            'enregistrement_type.required' => 'Veuillez fournir Type Enregistrement',
            'action_delete.required' => 'Veuillez fournir dns nom and value',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $data2 = array();

                if(strcmp($request->enregistrement_name, "A") !== 0 ){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','arecs0' => $request->action_delete);
                }
                if(strcmp($request->enregistrement_name, "NS") !== 0 ){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','nsrecs0' => $request->action_delete);
                }
                if(strcmp($request->enregistrement_name, "MX") !== 0){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','mxrecs0' => $request->action_delete);
                }
                if(strcmp($request->enregistrement_name, "CNAME") !== 0){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','cnamerecs0' => $request->action_delete);
                }
                if(strcmp($request->enregistrement_name, "TXT") !== 0){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','txtrecs0' => $request->action_delete);
                }
                if(strcmp($request->enregistrement_name, "PTR       ") !== 0){
                    $data2 = array('domain'=>$request->domaine,  'action' => 'select',
                        'delete' => 'delete','ptrrecs0' => $request->action_delete);
                }
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_DNS_CONTROL',
                    'array' => $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);


                if ($res['error'] === "0") {
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }


            } else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDataBaseList(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
            $results = array();

                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_DB',
                    'array' => array('json'=>'yes','domain'=>$request->domaine),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);


                if ($res) {
                    return $res;
                    //return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                } else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }
            } else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else{
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainEmails(Request $request)
    {
        $data = $request->all();
        $rules = [
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_POP',
                    'array' =>  array('action'=>'full_list','domain' =>  $request->domaine),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

            $email = array();
            $array = array();
            if(count($res)> 0){


                $pieces = array();
                foreach ($res as $i => $arr){
                    $pieces = explode("&", $arr);
                    //   $pieces2 = explode(":", $arr);
                    // $end =  strpos($arr, ':');
                    foreach ($pieces as $arr1){

                        $start = strpos($arr1, '=');


                        $email = Arr::prepend($email,substr($arr1,$start+1), substr($arr1, 0, strpos($arr1, '=')));


                    }
                    $array2 = Arr::prepend($email, $i, 'name');
                    array_push($array,$array2);
                    $results = $array;
                }

            }
            else{
                $results = [];
            }
            return $this->successResponse($results, "Enregistrement effectué avec succès!", 201);
        }
        else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
        }

    }

    else{
        return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainCreateEmail(Request $request)
    {

        $data = $request->all();
        $rules = [
            'username'    => 'required',
            'passwd' =>  ['required', 'min:8'],
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'username' => "Nom d'utilisateur",
            'passwd' => "Mot de passe",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'username.required' => "Veuillez fournir nom d'utilisateur",
            'passwd.required' => "Veuillez fournir Mot de passe",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

        if($response['result'] === 'success'){

            if($request->quota > 0){
                $data2 =array('action'=>'create','domain' => $request->domaine,'user'=>$request->username,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2
                ,'quota'=>$request->quota);
            }else{
                $data2 = array('action'=>'create','domain' =>  $request->domaine,'user'=>$request->username,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2,'quota'=>0
                );
            }
            $DirectAdminData = [
                'serverhostname' => $response['products']['product'][0]['serverhostname'],
                'username' => $response['products']['product'][0]['username'],
                'password' => $response['products']['product'][0]['password'],
                'method' => 'GET',
                'query' => '/CMD_API_POP',
                'array' =>  $data2,


            ];

            $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

            if($res['error'] === "0"){

                return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);


            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }
        else{
            $response = ["message" => "quelque chose s'est mal passé"];
            return response($response, 422);
        }

    }
    else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainEditEmail(Request $request)
    {
        $data = $request->all();
        $rules = [
            'username'    => 'required',
            'passwd' =>  ['required', 'min:8'],
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'username' => "Nom d'utilisateur",
            'passwd' => "Mot de passe",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'username.required' => "Veuillez fournir nom d'utilisateur",
            'passwd.required' => "Veuillez fournir Mot de passe",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){

            $results = array();
                if($request->quota > 0){
                    $data2 =array('action'=>'modify','domain' => $request->domaine,'user'=>$request->username,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2
                    ,'quota'=>$request->quota);
                }else{
                    $data2 = array('action'=>'modify','domain' => $request->domaine,'user'=>$request->username,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2,'quota'=>0
                    );
                }
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_POP',
                    'array' =>  $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

                if($res['error'] === "0"){

                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);


                }
                else{
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }

            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainLogEmail(Request $request)
    {

        $data = $request->all();
        $rules = [
            'email'    => 'required',
            'methode' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'email' => "compte email",
            'methode' => "methode",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'email.required' => "Veuillez fournir compte email",
            'methode.required' => "Veuillez fournir method outgoing/incoming",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_EMAIL_USAGE',
                    'array' =>  array('action'=>'smtp_log','domain' =>$request->domaine,'user'=>$request->email,'method'=>$request->methode),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

                return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);

        }
        else{
            return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
        }
    }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDataBaseUsersList(Request $request)
    {
        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_DB_VIEW',
                    'array' => array('json'=>'yes','domain'=>$request->domaine,'name'=>$request->database_name),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);


            return    $res ;

        } else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
        }

    }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDataBaseListUsers(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',

        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_DB_CREATE',
                    'array' =>  array('json'=>'yes',''=>$request->database_name,'domain'=>$request->domaine),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);


            return   $res;

            } else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminAddDataBase(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'passwd' =>  'required',
            'passwd2' =>  'required',
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'passwd' =>  'Mot de passe',
            'passwd2' =>  'Confirmation mot de passe',
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'passwd.required' => "Veuillez fournir mot de passe",
            'passwd2.required' =>  'Confirmation mot de passe',
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_DATABASES',
                    'array' => array('action'=>'create','name' => $request->database_name,'user'=>$request->user_name,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

             //   return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
            if($res['error'] === "0"){
                return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
            }
            else
                {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }


            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminAddUserDataBase(Request $request)
    {
        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'passwd' =>  'required',
            'passwd2' =>  'required',
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'passwd' =>  'Mot de passe',
            'passwd2' =>  'Confirmation mot de passe',
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'passwd.required' => "Veuillez fournir mot de passe",
            'passwd2.required' =>  'Confirmation mot de passe',
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_DB',
                    'array' => array('json'=>'yes','action'=>'createuser','name' => $request->database_name,'user'=>$request->user_name,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);
            return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminAddExistingUserDataBase(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',



        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_DB',
                    'array' =>array('json'=>'yes','userlist'=>$request->user_name,'action'=>'createuser','name' => $request->database_name,'passwd'=>"*****",'passwd2'=>"*****"),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);
                return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDeleteDataBase(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',



        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_DATABASES',
                    'array' =>array('action'=>'delete','select0'=>($request->database_name)),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);
                if($res['error'] === "0"){
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                }
                else
                {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }


            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminDeleteUserDataBase(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',



        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',



        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
            $DirectAdminData = [
                'serverhostname' => $response['products']['product'][0]['serverhostname'],
                'username' => $response['products']['product'][0]['username'],
                'password' => $response['products']['product'][0]['password'],
                'method' => 'POST',
                'query' => '/CMD_DB',
                'array' =>array('json'=>'yes','select0'=>$request->user_name,'action'=>'deleteuser','name' => $request->database_name,'delete'=>"yes"),


            ];

            $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);
            return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUpdateUserPRIVILEGESDataBase(Request $request)
    {
        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',
            'privilege' => 'required'


        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
            'privilege' => 'privilege user'



        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
            'privilege.required' => 'Veuillez nous indiquer privilege user'


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $items =json_decode($request->privilege);
                $data2 = [];
                for ( $i = 0; $i < count($items); $i++ ) {
                    if($items[$i]->status === true){
                        $data2 += [ $items[$i]->text => "Y" ];
                    }else{
                        $data2 += [ $items[$i]->text  => "N" ];
                    }

                }

                $results = array_merge($data, array('domain'=>$request->domaine,'json'=>'yes','user'=>$request->user_name,'name' => $request->database_name));

                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_DB_USER_PRIVS',
                    'array' =>$results,


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);

                return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }



    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUpdatePasswordUserDataBase(Request $request)
    {

        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',
            'passwd' =>  'required',
            'passwd2' =>  'required',
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",
            'passwd' =>  'Mot de passe',
            'passwd2' =>  'Confirmation mot de passe',
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'passwd.required' => "Veuillez fournir mot de passe",
            'passwd2.required' =>  'Confirmation mot de passe',
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_DB',
                    'array' =>array('domain'=>$request->domaine,'json'=>'yes','user'=>$request->user_name,'action'=>'modifyuser','name' => $request->database_name,'passwd'=>$request->passwd,'passwd2'=>$request->passwd2),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);
                return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUserPRIVILEGESDataBase(Request $request)
    {
        $data = $request->all();
        $rules = [
            'database_name'    => 'required',
            'user_name' => 'required',

            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'database_name' => "Nom Base de données",
            'user_name' => "Nom d'utilisateur",

            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'database_name.required' => "Veuillez fournir Nom Base de données",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_DB_USER_PRIVS',
                    'array' =>array('domain'=>$request->domaine,'json'=>'yes','user'=>$request->user_name,'name' => $request->database_name),


                ];

                $res = $this->ClientConnectDirectAdminWithOutApi($request, $DirectAdminData);
                return  $res ;
            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function ClientDirectAdminDomainDeleteEmail(Request $request)
    {

        $data = $request->all();
        $rules = [
            'email'    => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(
            'email' => "compte email",

            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(
            'email.required' => "Veuillez fournir compte email",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){


                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_POP',
                    'array' =>array('action'=>'delete','domain' => $request->domaine,'user'=>$request->email
                    ),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);
                if($res['error'] === "0"){
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                }
                else
                {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }


            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUserFTP(Request $request)
    {
        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_FTP',
                    'array' =>array(
                        'domain'=>$request->domaine),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);


                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);




            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }



    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUserCreateFTPAccount(Request $request)
    {


        $data = $request->all();
        $rules = [
            'type' => 'required',
            'user_name' => 'required',
            'passwd' => 'required',
            'passwd2' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',

        ];
        $niceNames = array(
            'type' => "type ftp",
            'user_name' => "Nom d'utilisateur",
            'passwd' => 'Mot de passe',
            'passwd2' => 'Confirmation mot de passe',
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',


        );
        $messages = array(
            'type.required' => "Veuillez fournir type ftp",
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'passwd.required' => "Veuillez fournir mot de passe",
            'passwd2.required' => 'Confirmation mot de passe',

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
                $data2 = array();

                if ($request->type === 'custom') {

                    $data2 = array('action' => 'create', 'user' => $request->user_name, 'type' => $request->type, 'passwd' => $request->passwd, 'passwd2' => $request->passwd2, 'custom_val' => $request->custom_val, 'domain' => $request->domain);
                } else {
                    $data2 = array('action' => 'create', 'user' => $request->user_name, 'type' => $request->type, 'passwd' => $request->passwd, 'passwd2' => $request->passwd2, 'domain' => $request->domaine);

                }

                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'GET',
                    'query' => '/CMD_API_FTP',
                    'array' => $data2,


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);

                if ($res['error'] === "0") {
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                }
                else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }

            }
            else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }else{
            return $this->errorResponse(422, null, "vous n'êtes pas le propriétaire de ce projet");
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUserDeleteFTPAccount(Request $request)
    {

        $data = $request->all();
        $rules = [
            'user_name' => 'required',
            'domaine' => 'required',
            'id_projet' => 'required',
        ];
        $niceNames = array(
            'user_name' => "Nom d'utilisateur",
            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',
        );
        $messages = array(
            'user_name.required' => "Veuillez fournir un uom d'utilisateur",
            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',
        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if ($response['result'] === 'success') {
            $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_FTP',
                    'array' => array( 'domain'=>$request->domaine,'select0'=>$request->user_name,'action'=>'delete'),


                ];

                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);
                if ($res['error'] === "0") {
                    return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);
                }
                else {
                    return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
                }

            }
            else {
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }

        }else{
            return $this->errorResponse(422, null, "vous n'êtes pas le propriétaire de ce projet");
        }
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            'email'    => 'required|email',

            'telephone' =>'required',


            'country' => 'required',

            'nom' => 'required',
            'prenom' => 'required',
            'adresse' =>'required',
            'adresse2' =>'required',
            'ville' => 'required',
            'province' =>'required',
            'codepostal' => 'required',

        ];
        $niceNames = array(


            'country' => 'pays',
            'codepostal' => 'Code postal',

            'prenom' => 'prénom',


        );
        $messages = array(


            'email.required' => 'Veuillez nous indiquer votre adresse email!',
            'email_email' => 'Veuillez fournir une adresse valide!',
            'telephone.required' =>'Veuillez nous indiquer votre numéro de téléphone',
            //  'photo.required' => 'Veuillez fournir votre photo',
            'country.required' => 'Veuillez fournir votre pays',

            'nom.required' => 'Veuillez fournir votre nom',
            'prenom.required' => 'Veuillez fournir votre prénom',
            'adresse.required' =>'Veuillez fournir votre adresse',
            'adresse2.required' =>'Veuillez fournir votre adresse 2',
            'ville.required' => 'Veuillez fournir votre nom de ville',
            'province.required' =>'Veuillez fournir la province',
            'codepostal.required' => 'Veuillez fournir votre code postal',

        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return response()->json([
                'message' => 'failed',
                'error' => $validator->errors()
            ], 400);
        }
        $Demande = DemandeInscription::where('user_id','=' ,$request->user()->id)

            ->first();
        if(!$Demande){
            $response = ["message" =>"Demande n'existe pas"];
            return response($response, 422);
        }
        if(!empty($data['file'][0])) {


            $i = str_replace('/uploads', '', $Demande->photo);
            $i2 = str_replace('//', '/', $i);


            Storage::disk('uploads')->delete($i2);
            $imagepath = Storage::disk('uploads')->put('/' . $data['email'] . '/avatar/', $data['file'][0]);


            $img = '/uploads/' . $imagepath;
            $Demande->photo = $img;
        }
        $user = $request->user();
        $user->nom = $request->nom;
        $user->prenom = $request->prenom;
        $user->fullname = $request->prenom.' '.$request->nom;
        $user->save();
//        $Demande->email = $request->email;
//        //    $Demande->photo = $img;
        $Demande->country = $request->country;
        $Demande->tel_format_national = $request->tel_format_national;
        $Demande->country_code = $request->country_code;

        $Demande->nom = $request->nom;
        $Demande->prenom = $request->prenom;
        $Demande->adresse = $request->adresse;
        $Demande->adresse2 = $request->adresse2;
        $Demande->ville = $request->ville;
        $Demande->province= $request->province;
        $Demande->codepostal = $request->codepostal;
        $Demande->save();
        return response([
            'statut'=>"Mise a jour réussie!",
            'status'         => 200,


        ]);


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $request_data = $request->All();
        $rules = [
            'current-password' => 'required',
            'password' => 'required|same:password',
            'password_confirmation' => 'required|same:password',

        ];
        $niceNames = array(


            'current-password' => 'Mot de passe actuel',
            'password' => 'Nouveau mot de passe',

            'password_confirmation' => 'Confirmation mot de passe',


        );
        $messages = [
            'current-password.required' => '
Veuillez saisir le mot de passe actuel',
            'password.required' => 'Veuillez entrer le mot de passe',
        ];

        $validator = Validator::make($request_data, $rules,$messages, $niceNames);




        if($request->user())
        {


            if($validator->fails()) {
                return response()->json([
                    'message' => 'failed',
                    'error' => $validator->errors()
                ], 400);
            }
            else
            {
                $current_password =  $request->user()->password;
                if(Hash::check($request_data['current-password'], $current_password))
                {

                    $obj_user = $request->user();
                    $obj_user->password = Hash::make($request_data['password']);
                    $obj_user->save();
                    return response([
                        'statut'=>"Mise a jour réussie!",
                        'status'         => 200,


                    ]);
                }
                else
                {
                    $response = ["message" =>"Veuillez saisir le mot de passe actuel"];
                    return response($response, 422);
                }
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }





    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array|string
     */
    public function ClientDirectAdminUrllogin(Request $request)
    {
        $data = $request->all();
        $rules = [

            'domaine' => 'required',
            'id_projet' => 'required',


        ];
        $niceNames = array(

            'domaine' => 'nom de domaine',
            'id_projet' => 'id de projet',

        );
        $messages = array(

            'domaine.required' => 'Veuillez nous indiquer votre nom domaine!',
            'id_projet.required' => 'Veuillez fournir id de projet',


        );
        $validator = Validator::make($data, $rules, $messages, $niceNames);


        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
        }
        if ($this->CheckMemberShip($request)) {
            $project = $this->CheckMemberShip($request);
            $whdata = [


                'username' => $this->username_client,
                'password' => $this->password_client,
                'accesskey' => $this->accesskey,
                'action' => 'GetClientsProducts',
                'serviceid' => $project->serviceid,
                'clientid' => $project->Shared->wh_id,
                'stats' => true,
                'responsetype' => 'json',


            ];
            $response = $this->ClientConnectWH($request, $whdata);

            if($response['result'] === 'success'){
                $results = array();
                $DirectAdminData = [
                    'serverhostname' => $response['products']['product'][0]['serverhostname'],
                    'username' => $response['products']['product'][0]['username'],
                    'password' => $response['products']['product'][0]['password'],
                    'method' => 'POST',
                    'query' => '/CMD_API_LOGIN_KEYS',
                    'array' =>array(
                        'action'=>'create',
                        'keyname' => 'akeyname',
                        'key' => 'whatever',
                        'key2' => 'whatever',
                        'never_expires' => 'no',
                        'type'=>'one_time_url',
                        'expiry'=>'3d',
                            'redirect-url'=>'/CMD_ADDITIONAL_DOMAINS',
                        //'expiry_timestamp' => ?,
                        'max_uses' => 0,
                        'clear_key' => ' no',
                        'allow_html' => 'yes',
                        'passwd' => $response['products']['product'][0]['password'],
                        'select_allow0' => 'ALL_USER',
                        'select_deny0'=>'CMD_PASSWD',
                        'select_deny1'=>'CMD_LOGIN_KEYS',
                        'select_deny2'=>'CMD_API_LOGIN_KEYS'),



                ];
                $res = $this->ClientConnectDirectAdminWithArray($request, $DirectAdminData);


               return $this->successResponse($res, "Enregistrement effectué avec succès!", 201);




            }
            else{
                return $this->errorResponse(422, null, 'quelque chose s\'est mal passé');
            }
        }
        else
        {
            return $this->errorResponse(422,null,"vous n'êtes pas le propriétaire de ce projet");
        }
    }
}
