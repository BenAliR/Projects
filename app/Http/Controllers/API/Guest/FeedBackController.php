<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class FeedBackController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $feedback = Feedback::latest()->get();
            return $this->successResponse($feedback, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {

            return $this->errorResponse(404, ['Feedback introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($feedback, "Enregistrement effectué avec succès!", 201);

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
            'text' => 'required|string',
            'project_id' => 'required|exists:projects,id',

        ];
        $niceNames = array(

            'text' => 'required',


        );
        $messages = array(

            'content.required' => 'Veuillez fournir content!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        $feedback = Feedback::create(
  [          'content' => $request->text,
            'project_id' => $request->project_id,
            'user_id' => $request->user()->id,]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {

            $feedback = Feedback::findOrFail($id);
            if (!$feedback) {
                return $this->errorResponse(404, ["Feedback n'existe pas"], 'quelque chose s\'est mal passé');
            }

            if ($request->user()->id != intval($feedback->user_id)) {
                return $this->errorResponse(404, ["vous n'êtes pas le propriétaire"], 'quelque chose s\'est mal passé');
            }
            // Validate the request data
            $validator = Validator::make($request->all(), [

                'text' => 'required|string',
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $feedback->content = $request->input('text');
            $feedback->save();
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

            $feedback = Feedback::find($id);

            if (!$feedback) {

                return $this->errorResponse(404, ['Retour introuvable'], 'quelque chose s\'est mal passé');
            }


            $feedback->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Retour  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}
